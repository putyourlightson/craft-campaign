<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\elements;

use Craft;
use craft\base\Element;
use craft\elements\actions\Delete;
use craft\elements\actions\Duplicate;
use craft\elements\actions\Edit;
use craft\elements\actions\Restore;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\User;
use craft\helpers\ElementHelper;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\web\CpScreenResponseBehavior;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\conditions\contacts\ContactCondition;
use putyourlightson\campaign\elements\db\SegmentElementQuery;
use putyourlightson\campaign\fieldlayoutelements\segments\SegmentFieldLayoutTab;
use putyourlightson\campaign\records\SegmentRecord;
use yii\web\Response;

/**
 * @property-read int $contactCount
 * @property-read int $conditionCount
 * @property-read null|string $postEditUrl
 * @property-read array[] $crumbs
 */
class SegmentElement extends Element
{
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
    public static function trackChanges(): bool
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
     */
    public static function find(): SegmentElementQuery
    {
        return new SegmentElementQuery(static::class);
    }

    /**
     * @inheritdoc
     */
    protected static function defineSources(string $context): array
    {
        return [
            [
                'key' => '*',
                'label' => Craft::t('campaign', 'All segments'),
                'criteria' => [],
            ],
        ];
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

        // Duplicate
        $actions[] = $elementsService->createAction([
            'type' => Duplicate::class,
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
            'contacts' => ['label' => Craft::t('campaign', 'Contacts')],
            'slug' => ['label' => Craft::t('app', 'Slug')],
            'dateCreated' => ['label' => Craft::t('app', 'Date Created')],
            'dateUpdated' => ['label' => Craft::t('app', 'Date Updated')],
        ];
    }

    /**
     * @inheritdoc
     */
    protected static function defineDefaultTableAttributes(string $source): array
    {
        return ['contacts'];
    }

    /**
     * @inheritdoc
     */
    protected function attributeHtml(string $attribute): string
    {
        return match ($attribute) {
            'contacts' => (string)$this->getContactCount(),
            default => parent::attributeHtml($attribute),
        };
    }

    /**
     * @var ElementConditionInterface|null
     * @see getContactCondition()
     * @see setContactCondition()
     */
    private ?ElementConditionInterface $contactCondition = null;

    /**
     * @var ContactElement[]|null
     */
    private ?array $contacts = null;

    /**
     * @var int[]|null
     */
    private ?array $contactIds = null;

    /**
     * @inheritdoc
     * @since 3.0.0
     */
    protected function crumbs(): array
    {
        return [
            [
                'label' => Craft::t('campaign', 'Segments'),
                'url' => UrlHelper::url('campaign/segments'),
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['contactCondition'], 'safe'];

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getPostEditUrl(): ?string
    {
        return UrlHelper::cpUrl("campaign/segments");
    }

    /**
     * @inheritdoc
     * @since 2.0.0
     */
    public function prepareEditScreen(Response $response, string $containerId): void
    {
        /** @var Response|CpScreenResponseBehavior $response */
        $response->selectedSubnavItem = 'segments';
    }

    /**
     * @inheritdoc
     * @since 2.0.0
     */
    protected function cpEditUrl(): ?string
    {
        $path = sprintf('campaign/segments/%s', $this->getCanonicalId());

        // Ignore homepage/temp slugs
        if ($this->slug && !str_starts_with($this->slug, '__')) {
            $path .= "-$this->slug";
        }

        return UrlHelper::cpUrl($path);
    }

    /**
     * @inheritdoc
     * @since 2.0.0
     */
    public function getFieldLayout(): ?FieldLayout
    {
        $fieldLayout = new FieldLayout();
        $fieldLayout->setTabs([
            new SegmentFieldLayoutTab(),
        ]);

        return $fieldLayout;
    }

    /**
     * @inheritdoc
     * @since 2.0.0
     */
    protected function metaFieldsHtml(bool $static): string
    {
        return $this->slugFieldHtml($static) . parent::metaFieldsHtml($static);
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
     * @inheritdoc
     * @since 2.0.0
     */
    public function canView(User $user): bool
    {
        if (parent::canView($user)) {
            return true;
        }

        return $user->can('campaign:segments');
    }

    /**
     * @inheritdoc
     * @since 2.0.0
     */
    public function canSave(User $user): bool
    {
        if (parent::canSave($user)) {
            return true;
        }

        return $user->can('campaign:segments');
    }

    /**
     * @inheritdoc
     * @since 2.0.0
     */
    public function canDuplicate(User $user): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     * @since 2.0.0
     */
    public function canDelete(User $user): bool
    {
        if (parent::canDelete($user)) {
            return true;
        }

        return $user->can('campaign:segments');
    }

    /**
     * @inheritdoc
     * @since 2.0.0
     */
    public function canCreateDrafts(User $user): bool
    {
        return true;
    }

    /**
     * Returns the contact condition.
     */
    public function getContactCondition(): ElementConditionInterface
    {
        $condition = $this->contactCondition ?? ContactElement::createCondition();
        $condition->mainTag = 'div';
        $condition->name = 'contactCondition';

        return $condition;
    }

    /**
     * Sets the contact condition.
     */
    public function setContactCondition(ElementConditionInterface|array|string|null $condition): void
    {
        if (is_string($condition)) {
            $condition = Json::decodeIfJson($condition);
        }

        if (!$condition instanceof ElementConditionInterface) {
            $condition['class'] = ContactCondition::class;
            $condition = Craft::$app->getConditions()->createCondition($condition);
        }
        $condition->forProjectConfig = false;

        /** @var ContactCondition $condition */
        $this->contactCondition = $condition;
    }

    /**
     * Returns the number of conditions.
     */
    public function getConditionCount(): int
    {
        return count($this->getContactCondition()->getConditionRules());
    }

    /**
     * Returns the contacts.
     *
     * @return ContactElement[]
     */
    public function getContacts(): array
    {
        if ($this->contacts !== null) {
            return $this->contacts;
        }

        $this->contacts = Campaign::$plugin->segments->getContacts($this);

        return $this->contacts;
    }

    /**
     * Returns the contact IDs.
     *
     * @return int[]
     */
    public function getContactIds(): array
    {
        if ($this->contactIds !== null) {
            return $this->contactIds;
        }

        $this->contactIds = Campaign::$plugin->segments->getContactIds($this);

        return $this->contactIds;
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
    public function beforeSave(bool $isNew): bool
    {
        // Ensure the slug is unique, even though segments don't have URIs,
        // which prevents us from depending on the SlugValidator class.
        ElementHelper::setUniqueUri($this);

        return parent::beforeSave($isNew);
    }

    /**
     * @inheritdoc
     */
    public function afterSave(bool $isNew): void
    {
        if (!$this->propagating) {
            if ($isNew) {
                $segmentRecord = new SegmentRecord();
                $segmentRecord->id = $this->id;
            } else {
                $segmentRecord = SegmentRecord::findOne($this->id);
            }

            $segmentRecord->contactCondition = $this->getContactCondition()->getConfig();
            $segmentRecord->save(false);
        }

        parent::afterSave($isNew);
    }
}
