<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\elements;

use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\db\SegmentElementQuery;
use putyourlightson\campaign\events\RegisterSegmentAvailableFieldsEvent;
use putyourlightson\campaign\events\RegisterSegmentFieldOperatorsEvent;
use putyourlightson\campaign\helpers\StringHelper;
use putyourlightson\campaign\records\SegmentRecord;

use Craft;
use craft\base\Element;
use craft\base\Field;
use craft\behaviors\FieldLayoutBehavior;
use craft\elements\db\ElementQueryInterface;
use craft\elements\actions\Edit;
use craft\elements\actions\Delete;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use yii\base\InvalidConfigException;

/**
 * SegmentElement
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 *
 * @property int $conditionCount
 * @property int $contactCount
 * @property int[] $contactIds
 * @property array $fieldOperators
 * @property array $availableFields
 * @property array|\putyourlightson\campaign\elements\ContactElement[] $contacts
 */
class SegmentElement extends Element
{
    // Constants
    // =========================================================================

    /**
     * @event RegisterSegmentFieldOperatorsEvent
     */
    const EVENT_REGISTER_FIELD_OPERATORS = 'registerFieldOperators';

    /**
     * @event RegisterSegmentAvailableFieldsEvent
     */
    const EVENT_REGISTER_AVAILABLE_FIELDS = 'registerAvailableFields';

    // Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('campaign', 'Segment');
    }

    /**
     * @inheritdoc
     */
    public static function hasContent(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function hasTitles(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function isLocalized(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function hasStatuses(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     *
     * @return SegmentElementQuery
     */
    public static function find(): ElementQueryInterface
    {
        return new SegmentElementQuery(static::class);
    }

    /**
     * @inheritdoc
     */
    protected static function defineSources(string $context = null): array
    {
        $sources = [
            [
                'key' => '*',
                'label' => Craft::t('campaign', 'All segments'),
                'criteria' => []
            ]
        ];

        return $sources;
    }

    /**
     * @inheritdoc
     */
    protected static function defineActions(string $source = null): array
    {
        $actions = [];

        // Edit
        $actions[] = Craft::$app->getElements()->createAction([
            'type' => Edit::class,
            'label' => Craft::t('campaign', 'Edit segment'),
        ]);

        // Delete
        $actions[] = Craft::$app->getElements()->createAction([
            'type' => Delete::class,
            'confirmationMessage' => Craft::t('campaign', 'Are you sure you want to delete the selected segments? This action cannot be undone.'),
            'successMessage' => Craft::t('campaign', 'Segments deleted.'),
        ]);

        return $actions;
    }

    /**
     * @inheritdoc
     */
    protected static function defineSortOptions(): array
    {
        return [
            'title' => Craft::t('app', 'Title'),
            'elements.dateCreated' => Craft::t('app', 'Date Created'),
            'elements.dateUpdated' => Craft::t('app', 'Date Updated'),
        ];
    }

    /**
     * @inheritdoc
     */
    protected static function defineTableAttributes(): array
    {
        $attributes = [
            'title' => ['label' => Craft::t('app', 'Title')],
            'conditions' => ['label' => Craft::t('campaign', 'Conditions')],
            'contacts' => ['label' => Craft::t('campaign', 'Contacts')],
            'dateCreated' => ['label' => Craft::t('app', 'Date Created')],
            'dateUpdated' => ['label' => Craft::t('app', 'Date Updated')],
        ];

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    protected function tableAttributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'conditions':
                return $this->getConditionCount();

            case 'contacts':
                return $this->getContactCount();
        }

        return parent::tableAttributeHtml($attribute);
    }

    // Properties
    // =========================================================================

    /**
     * @var mixed Conditions
     */
    public $conditions;

    /**
     * @var array|null
     */
    private $_contacts;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        // Decode JSON properties
        $this->conditions = empty($this->conditions) ? [] : Json::decode($this->conditions);
    }

    /**
     * @inheritdoc
     */
    public function getSupportedSites(): array
    {
        if ($this->siteId !== null) {
            return [$this->siteId];
        }

        return parent::getSupportedSites();
    }

    /**
     * Returns field operators
     *
     * @return array
     */
    public function getFieldOperators(): array
    {
        $fieldOperators = [
            'plaintext' =>  [
                '=' => Craft::t('campaign', 'is'),
                '!=' => Craft::t('campaign', 'is not'),
                'like' => Craft::t('campaign', 'contains'),
                'not like' => Craft::t('campaign', 'does not contain'),
                'like v%' => Craft::t('campaign', 'starts with'),
                'not like v%' => Craft::t('campaign', 'does not start with'),
                'like %v' => Craft::t('campaign', 'ends with'),
                'not like %v' => Craft::t('campaign', 'does not end with'),
            ],
            'number' => [
                '=' => Craft::t('campaign', 'equals'),
                '!=' => Craft::t('campaign', 'does not equal'),
                '<' => Craft::t('campaign', 'is less than'),
                '>' => Craft::t('campaign', 'is greater than'),
            ],
            'date' => [
                '=' => Craft::t('campaign', 'is on'),
                '<' => Craft::t('campaign', 'is before'),
                '>' => Craft::t('campaign', 'is after'),
            ],
            'template' => [
                '1' => Craft::t('campaign', 'evaluates to true'),
                '0' => Craft::t('campaign', 'evaluates to false'),
            ],
        ];

        $event = new RegisterSegmentFieldOperatorsEvent([
            'fieldOperators' => $fieldOperators
        ]);
        $this->trigger(self::EVENT_REGISTER_FIELD_OPERATORS, $event);

        return $event->fieldOperators;
    }

    /**
     * Returns available fields
     *
     * @return array
     * @throws InvalidConfigException
     */
    public function getAvailableFields(): array
    {
        $settings = Campaign::$plugin->getSettings();
        $availableFields = [[
            'type' => 'plaintext',
            'handle' => 'email',
            'name' => $settings->emailFieldLabel,
        ]];

        /** @var FieldLayoutBehavior $fieldLayoutBehavior */
        $fieldLayoutBehavior = $settings->getBehavior('contactFieldLayout');
        $fieldLayout = $fieldLayoutBehavior->getFieldLayout();

        if ($fieldLayout !== null) {
            $fieldOperators = $this->getFieldOperators();
            $fields = $fieldLayout->getFields();
            foreach ($fields as $field) {
                /* @var Field $field */
                $fieldType = strtolower(StringHelper::getClassName(\get_class($field)));
                if (isset($fieldOperators[$fieldType])) {
                    $availableFields[] = [
                        'type' => $fieldType,
                        'handle' => 'field_'.$field->handle,
                        'name' => $field->name,
                    ];
                }
            }
        }

        // Add date fields
        $availableFields[] = [
            'type' => 'date',
            'handle' => 'lastActivity',
            'name' => Craft::t('campaign', 'Last Activity'),
        ];
        $availableFields[] = [
            'type' => 'date',
            'handle' => 'elements.dateCreated',
            'name' => Craft::t('campaign', 'Date Created'),
        ];

        // Add template code field
        $availableFields[] = [
            'type' => 'template',
            'handle' => 'template',
            'name' => Craft::t('campaign', 'Template'),
        ];

        $event = new RegisterSegmentAvailableFieldsEvent([
            'availableFields' => $availableFields
        ]);
        $this->trigger(self::EVENT_REGISTER_AVAILABLE_FIELDS, $event);

        return $event->availableFields;
    }

    /**
     * Returns the number of conditions
     *
     * @return int
     */
    public function getConditionCount(): int
    {
        $count = 0;

        if (!\is_array($this->conditions)) {
            return $count;
        }

        foreach ($this->conditions as $conditions) {
            $count += \count($conditions);
        }

        return $count;
    }

    /**
     * Returns the contacts
     *
     * @return ContactElement[]
     */
    public function getContacts(): array
    {
        if ($this->_contacts !== null) {
            return $this->_contacts;
        }

        $this->_contacts = Campaign::$plugin->segments->getContacts($this);

        return $this->_contacts;
    }

    /**
     * Returns the contact IDs
     *
     * @return int[]
     */
    public function getContactIds(): array
    {
        $contactIds = [];

        foreach ($this->getContacts() as $contact) {
            $contactIds[] = $contact->id;
        }

        return $contactIds;
    }

    /**
     * Returns the number of contacts
     *
     * @return int
     */
    public function getContactCount(): int
    {
        return count($this->getContacts());
    }

    /**
     * @inheritdoc
     */
    public function getIsEditable(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getCpEditUrl()
    {
        return UrlHelper::cpUrl('campaign/segments/'.$this->id);
    }

    // Indexes, etc.
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public function getEditorHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('campaign/segments/_includes/titlefield', [
            'segment' => $this,
        ]);
    }

    // Events
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public function afterSave(bool $isNew)
    {
        if ($isNew) {
            $segmentRecord = new SegmentRecord();
            $segmentRecord->id = $this->id;
        } else {
            $segmentRecord = SegmentRecord::findOne($this->id);
        }

        if ($segmentRecord) {
            // Set attributes
            $segmentRecord->conditions = $this->conditions;

            $segmentRecord->save(false);
        }

        parent::afterSave($isNew);
    }
}