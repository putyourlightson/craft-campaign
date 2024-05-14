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
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\User;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\Html;
use craft\helpers\Template;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\validators\DateTimeValidator;
use craft\web\CpScreenResponseBehavior;
use DateTime;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\actions\SubscribeContacts;
use putyourlightson\campaign\elements\actions\UnsubscribeContacts;
use putyourlightson\campaign\elements\conditions\contacts\ContactCondition;
use putyourlightson\campaign\elements\db\ContactElementQuery;
use putyourlightson\campaign\fieldlayoutelements\contacts\ContactMailingListFieldLayoutTab;
use putyourlightson\campaign\fieldlayoutelements\reports\ContactReportFieldLayoutTab;
use putyourlightson\campaign\helpers\StringHelper;
use putyourlightson\campaign\records\ContactMailingListRecord;
use putyourlightson\campaign\records\ContactRecord;
use putyourlightson\campaign\validators\UniqueContactEmailValidator;
use yii\i18n\Formatter;
use yii\web\Response;

class ContactElement extends Element
{
    /**
     * @const string
     */
    public const STATUS_ACTIVE = 'active';

    /**
     * @const string
     */
    public const STATUS_COMPLAINED = 'complained';

    /**
     * @const string
     */
    public const STATUS_BOUNCED = 'bounced';

    /**
     * @const string
     */
    public const STATUS_BLOCKED = 'blocked';

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('campaign', 'Contact');
    }

    /**
     * @inheritdoc
     */
    public static function lowerDisplayName(): string
    {
        return Craft::t('campaign', 'contact');
    }

    /**
     * @inheritdoc
     */
    public static function pluralDisplayName(): string
    {
        return Craft::t('campaign', 'Contacts');
    }

    /**
     * @inheritdoc
     */
    public static function pluralLowerDisplayName(): string
    {
        return Craft::t('campaign', 'contacts');
    }

    /**
     * @inheritdoc
     */
    public static function refHandle(): ?string
    {
        return 'contact';
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
    public static function hasContent(): bool
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
    public static function statuses(): array
    {
        return [
            self::STATUS_ACTIVE => Craft::t('campaign', 'Active'),
            self::STATUS_COMPLAINED => Craft::t('campaign', 'Complained'),
            self::STATUS_BOUNCED => Craft::t('campaign', 'Bounced'),
            self::STATUS_BLOCKED => Craft::t('campaign', 'Blocked'),
        ];
    }

    /**
     * @inheritdoc
     */
    public static function find(): ContactElementQuery
    {
        return new ContactElementQuery(static::class);
    }

    /**
     * @inheritdoc
     * @return ContactCondition
     */
    public static function createCondition(): ElementConditionInterface
    {
        return Craft::createObject(ContactCondition::class, [static::class]);
    }

    /**
     * @inheritdoc
     */
    protected static function defineSources(string $context): array
    {
        $sources = [
            [
                'key' => '*',
                'label' => Craft::t('campaign', 'All contacts'),
                'criteria' => [],
                'hasThumbs' => true,
            ],
            [
                'heading' => Craft::t('campaign', 'Mailing Lists'),
            ],
        ];
        $mailingLists = Campaign::$plugin->mailingLists->getAllMailingLists();

        foreach ($mailingLists as $mailingList) {
            $sources[] = [
                'key' => 'mailingList:' . $mailingList->uid,
                'label' => $mailingList->title,
                'sites' => [$mailingList->siteId],
                'data' => ['id' => $mailingList->id],
                'criteria' => ['mailingListId' => $mailingList->id],
                'hasThumbs' => true,
            ];
        }

        if (Campaign::$plugin->getIsPro()) {
            $segments = Campaign::$plugin->segments->getAllSegments();

            if (!empty($segments)) {
                $sources[] = ['heading' => Craft::t('campaign', 'Segments')];

                foreach ($segments as $segment) {
                    $sources[] = [
                        'key' => 'segment:' . $segment->uid,
                        'label' => $segment->title,
                        'sites' => [$segment->siteId],
                        'data' => ['id' => $segment->id],
                        'criteria' => ['segmentId' => $segment->id],
                        'hasThumbs' => true,
                    ];
                }
            }
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

        // Subscribe/unsubscribe
        $mailingLists = Campaign::$plugin->mailingLists->getAllMailingLists();
        foreach ($mailingLists as $mailingList) {
            $actions[] = $elementsService->createAction([
                'type' => SubscribeContacts::class,
                'mailingListId' => $mailingList->id,
                'mailingListTitle' => $mailingList->title,
            ]);
        }
        foreach ($mailingLists as $mailingList) {
            $actions[] = $elementsService->createAction([
                'type' => UnsubscribeContacts::class,
                'mailingListId' => $mailingList->id,
                'mailingListTitle' => $mailingList->title,
            ]);
        }

        // Edit
        $actions[] = $elementsService->createAction([
            'type' => Edit::class,
            'label' => Craft::t('campaign', 'Edit contact'),
        ]);

        // Delete
        $actions[] = $elementsService->createAction([
            'type' => Delete::class,
            'confirmationMessage' => Craft::t('campaign', 'Are you sure you want to delete the selected contacts?'),
            'successMessage' => Craft::t('campaign', 'Contacts deleted.'),
        ]);

        // Hard delete
        $actions[] = $elementsService->createAction([
            'type' => Delete::class,
            'hard' => true,
            'confirmationMessage' => Craft::t('campaign', 'Are you sure you want to permanently delete the selected contacts? This action cannot be undone.'),
            'successMessage' => Craft::t('campaign', 'Contacts permanently deleted.'),
        ]);

        // Restore
        $actions[] = $elementsService->createAction([
            'type' => Restore::class,
            'successMessage' => Craft::t('campaign', 'Contacts restored.'),
            'partialSuccessMessage' => Craft::t('campaign', 'Some contacts restored.'),
            'failMessage' => Craft::t('campaign', 'Contacts not restored.'),
        ]);

        return $actions;
    }

    /**
     * @inheritdoc
     */
    protected static function defineSortOptions(): array
    {
        $settings = Campaign::$plugin->settings;

        return [
            [
                'label' => $settings->getEmailFieldLabel(),
                'orderBy' => 'campaign_contacts.email',
                'attribute' => 'title',
            ],
            'cid' => Craft::t('campaign', 'Contact ID'),
            'subscriptionStatus' => Craft::t('campaign', 'Subscription Status'),
            'country' => Craft::t('campaign', 'Country'),
            'lastActivity' => Craft::t('campaign', 'Last Activity'),
            'verified' => Craft::t('campaign', 'Verified'),
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
            'subscriptionStatus' => ['label' => Craft::t('campaign', 'Subscription Status')],
            'country' => ['label' => Craft::t('campaign', 'Country')],
            'lastActivity' => ['label' => Craft::t('campaign', 'Last Activity')],
            'verified' => ['label' => Craft::t('campaign', 'Verified')],
            'dateCreated' => ['label' => Craft::t('app', 'Date Created')],
            'dateUpdated' => ['label' => Craft::t('app', 'Date Updated')],
        ];
    }

    /**
     * @inheritdoc
     */
    protected static function defineDefaultTableAttributes(string $source): array
    {
        $attributes = [];

        if ($source !== '*') {
            $attributes[] = 'subscriptionStatus';
        }

        $attributes[] = 'country';
        $attributes[] = 'lastActivity';

        return $attributes;
    }

    /**
     * @var int|null User ID
     */
    public ?int $userId = null;

    /**
     * @var null|string Contact ID
     */
    public ?string $cid = null;

    /**
     * @var string|null Email
     */
    public ?string $email = null;

    /**
     * @var string|null Country name
     */
    public ?string $country = null;

    /**
     * @var array|null GeoIP
     */
    public ?array $geoIp = null;

    /**
     * @var string|null Device
     */
    public ?string $device = null;

    /**
     * @var string|null OS
     */
    public ?string $os = null;

    /**
     * @var string|null Client
     */
    public ?string $client = null;

    /**
     * @var DateTime|null Last activity
     */
    public ?DateTime $lastActivity = null;

    /**
     * @var DateTime|null Verified
     */
    public ?DateTime $verified = null;

    /**
     * @var DateTime|null Complained
     */
    public ?DateTime $complained = null;

    /**
     * @var DateTime|null Bounced
     */
    public ?DateTime $bounced = null;

    /**
     * @var DateTime|null Blocked
     */
    public ?DateTime $blocked = null;

    /**
     * @var string|null Subscription status, only used when a mailing list is selected in element index
     */
    public ?string $subscriptionStatus = null;

    /**
     * @var string|null The initial email value, if there was one.
     * @see getDirtyAttributes()
     */
    private ?string $savedEmail = null;

    /**
     * @var null|FieldLayout Field layout
     * @see getFieldLayout()
     */
    private ?FieldLayout $fieldLayout = null;

    /**
     * @inheritdoc
     */
    public function __toString(): string
    {
        return $this->email ?? '';
    }

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        $this->savedEmail = $this->email;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        $labels = parent::attributeLabels();

        // Set the email field label
        $labels['email'] = Campaign::$plugin->settings->getEmailFieldLabel();

        return $labels;
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['cid', 'email'], 'required', 'on' => [self::SCENARIO_DEFAULT, self::SCENARIO_LIVE]];
        $rules[] = [['cid'], 'string', 'max' => 17];
        $rules[] = [['email'], 'email'];
        $rules[] = [['email'], UniqueContactEmailValidator::class, 'targetClass' => ContactRecord::class, 'on' => [self::SCENARIO_DEFAULT, self::SCENARIO_LIVE]];
        $rules[] = [['lastActivity', 'verified', 'complained', 'bounced', 'blocked'], DateTimeValidator::class];

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getPostEditUrl(): ?string
    {
        return UrlHelper::cpUrl("campaign/contacts");
    }

    /**
     * @inheritdoc
     * @since 2.0.0
     */
    public function getFieldLayout(): ?FieldLayout
    {
        // Memoize the field layout to ensure we don't end up with duplicate extra tabs!
        if ($this->fieldLayout !== null) {
            return $this->fieldLayout;
        }

        $this->fieldLayout = parent::getFieldLayout() ?? Campaign::$plugin->settings->getContactFieldLayout();

        if (!Craft::$app->getRequest()->getIsCpRequest()) {
            return $this->fieldLayout;
        }

        $this->fieldLayout->setTabs(array_merge(
            $this->fieldLayout->getTabs(),
            [
                new ContactMailingListFieldLayoutTab(),
                new ContactReportFieldLayoutTab(),
            ],
        ));

        return $this->fieldLayout;
    }

    /**
     * @inheritdoc
     * @since 2.0.0
     */
    protected function metadata(): array
    {
        $formatter = Craft::$app->getFormatter();
        $metadata = parent::metadata();

        if ($syncedUser = $this->getUser()) {
            $metadata[Craft::t('campaign', 'Synced')] = Cp::elementHtml($syncedUser);
        }

        $metadata[Craft::t('campaign', 'CID')] = Html::tag('code', $this->cid);

        if ($this->lastActivity) {
            $metadata[Craft::t('campaign', 'Last activity')] = $formatter->asDatetime($this->lastActivity, Formatter::FORMAT_WIDTH_SHORT);
        }

        if ($this->verified) {
            $metadata[Craft::t('campaign', 'Verified')] = $formatter->asDatetime($this->verified, Formatter::FORMAT_WIDTH_SHORT);
        }

        return $metadata;
    }

    /**
     * @inheritdoc
     */
    protected function statusFieldHtml(): string
    {
        return '';
    }

    /**
     * @inheritdoc
     * @since 2.0.0
     */
    public function canView(User $user): bool
    {
        if (!Campaign::$plugin->canUserEditContacts()) {
            return false;
        }

        if (parent::canView($user)) {
            return true;
        }

        return $user->can('campaign:contacts');
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

        return $user->can('campaign:contacts');
    }

    /**
     * @inheritdoc
     * @since 2.0.0
     */
    public function canDuplicate(User $user): bool
    {
        return false;
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

        return $user->can('campaign:contacts');
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
     * Returns the user that this contact is associated with.
     *
     * @since 1.15.1
     */
    public function getUser(): ?User
    {
        // Try synced user first
        if ($this->userId !== null) {
            return Craft::$app->getUsers()->getUserById($this->userId);
        }

        if ($this->email !== null) {
            return Craft::$app->getUsers()->getUserByUsernameOrEmail($this->email);
        }

        return null;
    }

    /**
     * Checks whether the contact is subscribed to at least one mailing list.
     */
    public function getIsSubscribed(): bool
    {
        return $this->getIsSubscribedTo();
    }

    /**
     * Checks whether the contact is subscribed to a provided mailing list,
     * or at least one mailing list if none is provided.
     */
    public function getIsSubscribedTo(MailingListElement $mailingList = null): bool
    {
        $condition = [
            'contactId' => $this->id,
            'subscriptionStatus' => 'subscribed',
        ];

        if ($mailingList !== null) {
            $condition['mailingListId'] = $mailingList->id;
        }

        return ContactMailingListRecord::find()
            ->where($condition)
            ->exists();
    }

    /**
     * Returns an unsubscribe URL.
     */
    public function getUnsubscribeUrl(SendoutElement $sendout = null): string
    {
        if ($this->cid === null) {
            return '';
        }

        $params = ['cid' => $this->cid];

        if ($sendout !== null) {
            $params['sid'] = $sendout->sid;
        }

        $path = Craft::$app->getConfig()->getGeneral()->actionTrigger . '/campaign/t/unsubscribe';

        return UrlHelper::siteUrl($path, $params);
    }

    /**
     * Returns the mailing list subscription status.
     */
    public function getMailingListSubscriptionStatus(int $mailingListId): string
    {
        /** @var ContactMailingListRecord|null $contactMailingList */
        $contactMailingList = ContactMailingListRecord::find()
            ->select(['subscriptionStatus'])
            ->where([
                'contactId' => $this->id,
                'mailingListId' => $mailingListId,
            ])
            ->one();

        return $contactMailingList->subscriptionStatus ?? '';
    }

    /**
     * Returns the number of subscribed mailing lists.
     */
    public function getSubscribedCount(): int
    {
        return $this->getMailingListCount('subscribed');
    }

    /**
     * Returns the number of unsubscribed mailing lists.
     */
    public function getUnsubscribedCount(): int
    {
        return $this->getMailingListCount('unsubscribed');
    }

    /**
     * Returns the number of complained mailing lists.
     */
    public function getComplainedCount(): int
    {
        return $this->getMailingListCount('complained');
    }

    /**
     * Returns the number of bounced mailing lists.
     */
    public function getBouncedCount(): int
    {
        return $this->getMailingListCount('bounced');
    }

    /**
     * Returns the subscribed mailing lists.
     */
    public function getSubscribedMailingLists(): array
    {
        return $this->getMailingListsWithStatus('subscribed');
    }

    /**
     * Returns the subscribed mailing lists.
     */
    public function getUnsubscribedMailingLists(): array
    {
        return $this->getMailingListsWithStatus('unsubscribed');
    }

    /**
     * Returns the complained mailing lists.
     */
    public function getComplainedMailingLists(): array
    {
        return $this->getMailingListsWithStatus('complained');
    }

    /**
     * Returns the bounced mailing lists.
     */
    public function getBouncedMailingLists(): array
    {
        return $this->getMailingListsWithStatus('bounced');
    }

    /**
     * Returns all mailing lists that this contact is in.
     */
    public function getMailingLists(): array
    {
        return $this->getMailingListsWithStatus();
    }

    /**
     * Returns the country code.
     */
    public function getCountryCode(): string
    {
        return $this->geoIp['countryCode'] ?? '';
    }

    /**
     * @inheritdoc
     */
    public function getStatus(): ?string
    {
        if ($this->complained) {
            return self::STATUS_COMPLAINED;
        }

        if ($this->bounced) {
            return self::STATUS_BOUNCED;
        }

        if ($this->blocked) {
            return self::STATUS_BLOCKED;
        }

        return self::STATUS_ACTIVE;
    }

    /**
     * @inheritdoc
     */
    public function getThumbUrl(int $size): ?string
    {
        // Get image from Gravatar, defaulting to a blank image
        return 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($this->email))) . '?d=blank&s=' . $size;
    }

    /**
     * @inheritdoc
     */
    public function getHasRoundedThumb(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getDirtyAttributes(): array
    {
        $dirtyAttributes = parent::getDirtyAttributes();

        if ($this->email !== $this->savedEmail && !in_array('email', $dirtyAttributes)) {
            $dirtyAttributes[] = 'email';
        }

        return $dirtyAttributes;
    }

    /**
     * @inheritdoc
     * @since 2.0.0
     */
    public function prepareEditScreen(Response $response, string $containerId): void
    {
        Craft::$app->getView()->registerJs('new Campaign.ContactEdit();');

        /** @var Response|CpScreenResponseBehavior $response */
        $response->selectedSubnavItem = 'contacts';

        $response->crumbs([
            [
                'label' => Craft::t('campaign', 'Contacts'),
                'url' => UrlHelper::url('campaign/contacts'),
            ],
        ]);

        if ($this->complained === null) {
            $response->addAltAction(
                Craft::t('campaign', 'Mark contact as complained'),
                [
                    'action' => 'campaign/contacts/mark-complained',
                    'confirm' => Craft::t('campaign', 'Are you sure you want to mark this contact as complained?'),
                ],
            );
        } else {
            $response->addAltAction(
                Craft::t('campaign', 'Unmark contact as complained'),
                [
                    'action' => 'campaign/contacts/unmark-complained',
                    'confirm' => Craft::t('campaign', 'Are you sure you want to unmark this contact as complained?'),
                ],
            );
        }

        if ($this->bounced === null) {
            $response->addAltAction(
                Craft::t('campaign', 'Mark contact as bounced'),
                [
                    'action' => 'campaign/contacts/mark-bounced',
                    'confirm' => Craft::t('campaign', 'Are you sure you want to mark this contact as bounced?'),
                ],
            );
        } else {
            $response->addAltAction(
                Craft::t('campaign', 'Unmark contact as bounced'),
                [
                    'action' => 'campaign/contacts/unmark-bounced',
                    'confirm' => Craft::t('campaign', 'Are you sure you want to unmark this contact as bounced?'),
                ],
            );
        }

        if ($this->blocked === null) {
            $response->addAltAction(
                Craft::t('campaign', 'Mark contact as blocked'),
                [
                    'action' => 'campaign/contacts/mark-blocked',
                    'confirm' => Craft::t('campaign', 'Are you sure you want to mark this contact as blocked?'),
                ],
            );
        } else {
            $response->addAltAction(
                Craft::t('campaign', 'Unmark contact as blocked'),
                [
                    'action' => 'campaign/contacts/unmark-blocked',
                    'confirm' => Craft::t('campaign', 'Are you sure you want to unmark this contact as blocked?'),
                ],
            );
        }
    }

    /**
     * @inheritdoc
     * @since 2.0.0
     */
    protected function cpEditUrl(): ?string
    {
        $path = sprintf('campaign/contacts/%s', $this->getCanonicalId());

        // Ignore homepage/temp slugs
        if ($this->slug && !str_starts_with($this->slug, '__')) {
            $path .= "-$this->slug";
        }

        return UrlHelper::cpUrl($path);
    }

    /**
     * Returns the contact's report URL.
     */
    public function getReportUrl(): string
    {
        return UrlHelper::cpUrl('campaign/reports/contacts/' . $this->id);
    }

    /**
     * Returns whether the contact can receive email.
     */
    public function canReceiveEmail(): bool
    {
        return $this->complained === null && $this->bounced === null && $this->blocked === null;
    }

    /**
     * @inheritdoc
     */
    protected static function defineSearchableAttributes(): array
    {
        return ['email', 'cid'];
    }

    /**
     * @inheritdoc
     */
    protected function tableAttributeHtml(string $attribute): string
    {
        if ($attribute == 'subscriptionStatus') {
            if ($this->subscriptionStatus) {
                return Template::raw('<label class="subscriptionStatus ' . $this->subscriptionStatus . '">' . $this->subscriptionStatus . '</label>');
            }
        }

        return parent::tableAttributeHtml($attribute);
    }

    /**
     * @inheritdoc
     */
    public function beforeSave(bool $isNew): bool
    {
        if ($isNew) {
            // Create unique ID
            $this->cid = StringHelper::uniqueId('c');
        }

        return parent::beforeSave($isNew);
    }

    /**
     * @inheritdoc
     */
    public function afterSave(bool $isNew): void
    {
        if (!$this->propagating) {
            if ($isNew) {
                $contactRecord = new ContactRecord();
                $contactRecord->id = $this->id;
                $contactRecord->cid = $this->cid;
            } else {
                $contactRecord = ContactRecord::findOne($this->id);
            }

            $contactRecord->userId = $this->userId;
            $contactRecord->email = $this->email;
            $contactRecord->country = $this->country;
            $contactRecord->geoIp = $this->geoIp;
            $contactRecord->device = $this->device;
            $contactRecord->os = $this->os;
            $contactRecord->client = $this->client;
            $contactRecord->lastActivity = $this->lastActivity;
            $contactRecord->verified = $this->verified;
            $contactRecord->complained = $this->complained;
            $contactRecord->bounced = $this->bounced;
            $contactRecord->blocked = $this->blocked;

            $contactRecord->save(false);
        }

        parent::afterSave($isNew);
    }

    /**
     * Returns the number of mailing lists that this contact is in.
     */
    private function getMailingListCount(string $subscriptionStatus = null): int
    {
        $subscriptionStatus = $subscriptionStatus ?? '';

        $condition = ['contactId' => $this->id];

        if ($subscriptionStatus) {
            $condition['subscriptionStatus'] = $subscriptionStatus;
        }

        return ContactMailingListRecord::find()
            ->where($condition)
            ->count();
    }

    /**
     * Returns the mailing lists that this contact is in with the provided status.
     *
     * @return MailingListElement[]
     */
    private function getMailingListsWithStatus(string $subscriptionStatus = null): array
    {
        $subscriptionStatus = $subscriptionStatus ?? '';

        $mailingLists = [];

        $condition = ['contactId' => $this->id];

        if ($subscriptionStatus) {
            $condition['subscriptionStatus'] = $subscriptionStatus;
        }

        /** @var ContactMailingListRecord[] $contactMailingLists */
        $contactMailingLists = ContactMailingListRecord::find()
            ->select(['mailingListId'])
            ->where($condition)
            ->all();

        foreach ($contactMailingLists as $contactMailingList) {
            $mailingList = Campaign::$plugin->mailingLists->getMailingListById($contactMailingList->mailingListId);

            if ($mailingList) {
                $mailingLists[] = $mailingList;
            }
        }

        ArrayHelper::multisort($mailingLists, 'title');

        return $mailingLists;
    }
}
