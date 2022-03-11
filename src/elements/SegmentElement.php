<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\elements;

use Craft;
use craft\base\Element;
use craft\elements\actions\Delete;
use craft\elements\actions\Edit;
use craft\elements\actions\Restore;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\db\SegmentElementQuery;
use putyourlightson\campaign\records\SegmentRecord;

/**
 * @property-read int $contactCount
 * @property-read bool $isEditable
 * @property-read string $segmentTypeLabel
 * @property-read int $conditionCount
 * @property-read ContactElement[] $contacts
 * @property-read int[] $contactIds
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
    public static function lowerDisplayName(): string
    {
        return Craft::t('campaign', 'segment');
    }

    /**
     * @inheritdoc
     */
    public static function pluralDisplayName(): string
    {
        return Craft::t('campaign', 'Segments');
    }

    /**
     * @inheritdoc
     */
    public static function pluralLowerDisplayName(): string
    {
        return Craft::t('campaign', 'segments');
    }

    /**
     * @inheritdoc
     */
    public static function refHandle(): ?string
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
                'criteria' => [],
            ],
        ];

        $sources[] = ['heading' => Craft::t('campaign', 'Segment Types')];

        $segmentTypes = self::segmentTypes();
        $index = 1;

        foreach ($segmentTypes as $segmentType => $label) {
            $sources[] = [
                'key' => 'segmentTypeId:' . $index,
                'label' => $label,
                'data' => [
                    'handle' => $segmentType,
                ],
                'criteria' => [
                    'segmentType' => $segmentType,
                ],
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
                'attribute' => 'dateCreated',
            ],
            [
                'label' => Craft::t('app', 'Date Updated'),
                'orderBy' => 'elements.dateUpdated',
                'attribute' => 'dateUpdated',
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    protected static function defineTableAttributes(): array
    {
        return [
            'title' => ['label' => Craft::t('app', 'Title')],
            'segmentType' => ['label' => Craft::t('campaign', 'Segment Type')],
            'conditions' => ['label' => Craft::t('campaign', 'Conditions')],
            'contacts' => ['label' => Craft::t('campaign', 'Contacts')],
            'dateCreated' => ['label' => Craft::t('app', 'Date Created')],
            'dateUpdated' => ['label' => Craft::t('app', 'Date Updated')],
        ];
    }

    /**
     * @inheritdoc
     */
    protected static function defineDefaultTableAttributes(string $source): array
    {
        if ($source == '*') {
            $attributes = ['title', 'segmentType', 'conditions', 'contacts'];
        } elseif ($source == 'regular') {
            $attributes = ['title', 'conditions', 'contacts'];
        } else {
            $attributes = ['title', 'contacts'];
        }


        return $attributes;
    }

    /**
     * @inheritdoc
     */
    protected function tableAttributeHtml(string $attribute): string
    {
        return match ($attribute) {
            'segmentType' => $this->getSegmentTypeLabel(),
            'conditions' => (string)$this->getConditionCount(),
            'contacts' => (string)$this->getContactCount(),
            default => parent::tableAttributeHtml($attribute),
        };
    }

    /**
     * @var string
     */
    public string $segmentType;

    /**
     * @var array|string|null
     */
    public string|array|null $conditions;

    /**
     * @var ContactElement[]|null
     */
    private ?array $_contacts;

    /**
     * @var int[]|null
     */
    private ?array $_contactIds;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        // Decode JSON properties
        $this->conditions = Json::decodeIfJson($this->conditions);
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        return [
            [['segmentType'], 'required'],
        ];
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
     */
    public function getSegmentTypeLabel(): string
    {
        $segmentTypes = self::segmentTypes();

        return $segmentTypes[$this->segmentType];
    }

    /**
     * Returns the number of conditions.
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
     * Returns the contacts.
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
     * Returns the contact IDs.
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
     * Returns the number of contacts.
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
    public function getCpEditUrl(): ?string
    {
        return UrlHelper::cpUrl('campaign/segments/' . $this->segmentType . '/' . $this->id);
    }

    /**
     * @inheritdoc
     */
    public function getEditorHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('campaign/segments/_includes/titlefield', [
            'segment' => $this,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function afterSave(bool $isNew): void
    {
        if ($isNew) {
            $segmentRecord = new SegmentRecord();
            $segmentRecord->id = $this->id;
        } else {
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
