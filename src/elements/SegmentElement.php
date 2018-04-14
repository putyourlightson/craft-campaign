<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\elements;

use putyourlightson\campaign\elements\db\ContactElementQuery;
use putyourlightson\campaign\elements\db\SegmentElementQuery;
use putyourlightson\campaign\records\SegmentRecord;

use Craft;
use craft\base\Element;
use craft\elements\db\ElementQueryInterface;
use craft\elements\actions\Edit;
use craft\elements\actions\Delete;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\UrlHelper;

/**
 * SegmentElement
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 *
 * @property int                                                       $conditionCount
 * @property int                                                       $contactCount
 * @property array|int[]                                               $contactIds
 * @property array|\putyourlightson\campaign\elements\ContactElement[] $contacts
 */
class SegmentElement extends Element
{
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
    public static function hasStatuses(): bool
    {
        return true;
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

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        // Decode conditions if not empty
        $this->conditions = !empty($this->conditions) ? Json::decode($this->conditions) : [];
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
     * Returns the number of contacts
     *
     * @return int
     */
    public function getContactCount(): int
    {
        return $this->_getContactElementQuery()->count();
    }

    /**
     * Returns the contact IDs
     *
     * @return int[]
     */
    public function getContactIds(): array
    {
        $query = $this->_getContactElementQuery();
        $query->select('elementsId as id');

        $contacts = $query->all();
        $contactIds = [];

        foreach ($contacts as $contact) {
            $contactIds[] = $contact->id;
        }

        return $contactIds;
    }

    /**
     * Returns the contacts
     *
     * @return ContactElement[]
     */
    public function getContacts(): array
    {
        $query = $this->_getContactElementQuery();

        return $query->all();
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

    // Private Methods
    // =========================================================================

    /**
     * Returns the contact element query
     *
     * @return ContactElementQuery
     */
    public function _getContactElementQuery(): ContactElementQuery
    {
        $query = ContactElement::find();

        if (!\is_array($this->conditions)) {
            return $query;
        }

        $condition = ['and'];

        foreach ($this->conditions as $andCondition) {
            $conditions = ['or'];
            /* @var array $andCondition */
            foreach ($andCondition as $orCondition) {
                $operator = $orCondition[0];

                // If operand contains %v
                if (strpos($operator, '%v') !== false) {
                    $orCondition[0] = trim(str_replace('%v', '', $orCondition[0]));
                    $orCondition[2] = '%'.$orCondition[2];
                    $orCondition[3] = false;
                }

                // If operand contains v%
                if (strpos($operator, 'v%') !== false) {
                    $orCondition[0] = trim(str_replace('v%', '', $orCondition[0]));
                    $orCondition[2] .= '%';
                    $orCondition[3] = false;
                }

                // Convert value if is a date
                if (preg_match('/\d{1,2}\/\d{1,2}\/\d{4}/', $orCondition[2])) {
                    $orCondition[2] = Db::prepareDateForDb(['date' => $orCondition[2]]) ?? '';
                }

                $conditions[] = $orCondition;
            }

            $condition[] = $conditions;
        }

        $query->where($condition);

        return $query;
    }
}