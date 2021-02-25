<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\elements;

use Craft;
use craft\base\Element;
use craft\elements\db\ElementQueryInterface;
use craft\elements\actions\Edit;
use craft\elements\actions\Delete;
use craft\elements\actions\Restore;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\db\SegmentElementQuery;
use putyourlightson\campaign\records\SegmentRecord;

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
 * @property string $segmentTypeLabel
 * @property ContactElement[] $contacts
 */
class SegmentElement extends Element
{
    // Static Methods
    // =========================================================================

    /**
     * Returns the segment types.
     *
     * @return array
     */
    public static function segmentTypes(): array
    {
        return [
            'regular' => Craft::t('campaign', 'Regular'),
            'template' => Craft::t('campaign', 'Template'),
        ];
    }

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
    public static function refHandle()
    {
        return 'segment';
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

        $sources[] = ['heading' => Craft::t('campaign', 'Segment Types')];

        $segmentTypes = self::segmentTypes();
        $index = 1;

        foreach ($segmentTypes as $segmentType => $label) {
            $sources[] = [
                'key' => 'segmentTypeId:'.$index,
                'label' => $label,
                'data' => [
                    'handle' => $segmentType
                ],
                'criteria' => [
                    'segmentType' => $segmentType
                ]
            ];

            $index++;
        }

        return $sources;
    }

    /**
     * @inheritdoc
     */
    protected static function defineActions(string $source = null): array
    {
        $actions = [];

        $elementsService = Craft::$app->getElements();

        // Edit
        $actions[] = $elementsService->createAction([
            'type' => Edit::class,
            'label' => Craft::t('campaign', 'Edit segment'),
        ]);

        // Delete
        $actions[] = $elementsService->createAction([
            'type' => Delete::class,
            'confirmationMessage' => Craft::t('campaign', 'Are you sure you want to delete the selected segments?'),
            'successMessage' => Craft::t('campaign', 'Segments deleted.'),
        ]);

        // Restore
        $actions[] = $elementsService->createAction([
            'type' => Restore::class,
            'successMessage' => Craft::t('campaign', 'Segments restored.'),
            'partialSuccessMessage' => Craft::t('campaign', 'Some segments restored.'),
            'failMessage' => Craft::t('campaign', 'Segments not restored.'),
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
            [
                'label' => Craft::t('app', 'Date Created'),
                'orderBy' => 'elements.dateCreated',
                'attribute' => 'dateCreated'
            ],
            [
                'label' => Craft::t('app', 'Date Updated'),
                'orderBy' => 'elements.dateUpdated',
                'attribute' => 'dateUpdated'
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    protected static function defineTableAttributes(): array
    {
        $attributes = [
            'title' => ['label' => Craft::t('app', 'Title')],
            'segmentType' => ['label' => Craft::t('campaign', 'Segment Type')],
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
    protected static function defineDefaultTableAttributes(string $source): array
    {
        if ($source == '*') {
            $attributes = ['title', 'segmentType', 'conditions', 'contacts'];
        }
        elseif ($source == 'regular') {
            $attributes = ['title', 'conditions', 'contacts'];
        }
        else {
            $attributes = ['title', 'contacts'];
        }


        return $attributes;
    }

    /**
     * @inheritdoc
     */
    protected function tableAttributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'segmentType':
                return $this->getSegmentTypeLabel();

            case 'conditions':
                return (string)$this->getConditionCount();

            case 'contacts':
                return (string)$this->getContactCount();
        }

        return parent::tableAttributeHtml($attribute);
    }

    // Properties
    // =========================================================================

    /**
     * @var string
     */
    public $segmentType;

    /**
     * @var array|string|null
     */
    public $conditions;

    /**
     * @var ContactElement[]|null
     */
    private $_contacts;

    /**
     * @var int[]|null
     */
    private $_contactIds;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        // Decode JSON properties
        $this->conditions = Json::decodeIfJson($this->conditions);
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        $rules = parent::rules();
        $rules[] = [['segmentType'], 'required'];

        return $rules;
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
     * Returns the segment type label for the given segment type.
     *
     * @return string
     */
    public function getSegmentTypeLabel(): string
    {
        $segmentTypes = self::segmentTypes();

        return $segmentTypes[$this->segmentType];
    }

    /**
     * Returns the number of conditions
     *
     * @return int
     */
    public function getConditionCount(): int
    {
        if ($this->segmentType == 'template') {
            return 1;
        }

        if (!is_array($this->conditions)) {
            return 0;
        }

        $count = 0;

        foreach ($this->conditions as $conditions) {
            $count += count($conditions);
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
        if ($this->_contactIds !== null) {
            return $this->_contactIds;
        }

        $this->_contactIds = Campaign::$plugin->segments->getContactIds($this);

        return $this->_contactIds;
    }

    /**
     * Returns the number of contacts
     *
     * @return int
     */
    public function getContactCount(): int
    {
        return count($this->getContactIds());
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
        return UrlHelper::cpUrl('campaign/segments/'.$this->segmentType.'/'.$this->id);
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
        }
        else {
            $segmentRecord = SegmentRecord::findOne($this->id);
        }

        if ($segmentRecord) {
            // Set attributes
            $segmentRecord->segmentType = $this->segmentType;
            $segmentRecord->conditions = Json::encode($this->conditions);

            $segmentRecord->save(false);
        }

        parent::afterSave($isNew);
    }
}
