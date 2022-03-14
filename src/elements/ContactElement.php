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
use craft\elements\User;
use craft\helpers\Json;
use craft\helpers\Template;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\validators\DateTimeValidator;
use craft\validators\UniqueValidator;
use DateTime;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\actions\HardDeleteContacts;
use putyourlightson\campaign\elements\db\ContactElementQuery;
use putyourlightson\campaign\fieldlayoutelements\contacts\ContactMailingListFieldLayoutTab;
use putyourlightson\campaign\fieldlayoutelements\reports\ContactReportFieldLayoutTab;
use putyourlightson\campaign\helpers\StringHelper;
use putyourlightson\campaign\records\ContactMailingListRecord;
use putyourlightson\campaign\records\ContactRecord;
use yii\i18n\Formatter;

/**
 * @property-read int $complainedCount
 * @property-read int $unsubscribedCount
 * @property-read MailingListElement[] $complainedMailingLists
 * @property-read int $bouncedCount
 * @property-read string $reportUrl
 * @property-read MailingListElement[] $unsubscribedMailingLists
 * @property-read MailingListElement[] $subscribedMailingLists
 * @property-read bool $hasRoundedThumb
 * @property-read bool $isSubscribed
 * @property-read MailingListElement[] $bouncedMailingLists
 * @property-read bool $isEditable
 * @property-read string $countryCode
 * @property-read int $subscribedCount
 * @property-read User|null $user
 * @property-read array[] $crumbs
 * @property-read MailingListElement[] $mailingLists
 */
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
     */
    protected static function defineSources(string $context = null): array
    {
        $sources = [
            [
                'key' => '*',
                'label' => Craft::t('campaign', 'All contacts'),
                'criteria' => [],
                'hasThumbs' => true,
            ],
        ];

        $sources[] = ['heading' => Craft::t('campaign', 'Mailing Lists')];

        $mailingLists = Campaign::$plugin->mailingLists->getAllMailingLists();

        foreach ($mailingLists as $mailingList) {
            $sources[] = [
                'key' => 'mailingList:' . $mailingList->id,
                'label' => $mailingList->title,
                'sites' => [$mailingList->siteId],
                'data' => [
                    'id' => $mailingList->id,
                ],
                'criteria' => [
                    'mailingListId' => $mailingList->id,
                ],
                'hasThumbs' => true,
            ];
        }

        if (Campaign::$plugin->getIsPro()) {
            $sources[] = ['heading' => Craft::t('campaign', 'Segments')];

            $segments = SegmentElement::findAll();

            foreach ($segments as $segment) {
                $sources[] = [
                    'key' => 'segment:' . $segment->id,
                    'label' => $segment->title,
                    'data' => [
                        'id' => $segment->id,
                    ],
                    'criteria' => [
                        'segmentId' => $segment->id,
                    ],
                    'hasThumbs' => true,
                ];
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
        $actions[] = HardDeleteContacts::class;

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
        $settings = Campaign::$plugin->getSettings();

        return [
            'email' => $settings->emailFieldLabel,
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
        $settings = Campaign::$plugin->getSettings();

        return [
            'email' => ['label' => $settings->emailFieldLabel],
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
        $attributes = ['email'];

        if ($source !== '*') {
            $attributes[] = 'subscriptionStatus';
        }

        array_push($attributes, 'country', 'lastActivity');

        return $attributes;
    }

    /**
     * User ID
     *
     * @var int|null
     */
    public ?int $userId = null;

    /**
     * Contact ID
     *
     * @var null|string
     */
    public ?string $cid = null;

    /**
     * Email
     *
     * @var string|null
     */
    public ?string $email = null;

    /**
     * Country name
     *
     * @var string|null
     */
    public ?string $country = null;

    /**
     * GeoIP
     *
     * @var mixed
     */
    public mixed $geoIp = null;

    /**
     * Device
     *
     * @var string|null
     */
    public ?string $device = null;

    /**
     * OS
     *
     * @var string|null
     */
    public ?string $os = null;

    /**
     * Client
     *
     * @var string|null
     */
    public ?string $client = null;

    /**
     * Last activity
     *
     * @var DateTime|null
     */
    public ?DateTime $lastActivity = null;

    /**
     * Verified
     *
     * @var DateTime|null
     */
    public ?DateTime $verified = null;

    /**
     * Complained
     *
     * @var DateTime|null
     */
    public ?DateTime $complained = null;

    /**
     * Bounced
     *
     * @var DateTime|null
     */
    public ?DateTime $bounced = null;

    /**
     * Blocked
     *
     * @var DateTime|null
     */
    public ?DateTime $blocked = null;

    /**
     * @var string|null Subscription status, only used when a mailing list is selected in element index
     */
    public ?string $subscriptionStatus = null;

    /**
     * @inheritdoc
     */
    public function __toString(): string
    {
        return $this->email;
    }

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        // Decode geoIp if not empty
        $this->geoIp = !empty($this->geoIp) ? Json::decode($this->geoIp) : null;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        $labels = parent::attributeLabels();

        // Set the email field label
        $labels['email'] = Campaign::$plugin->getSettings()->emailFieldLabel;

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
        $rules[] = [['email'], UniqueValidator::class, 'targetClass' => ContactRecord::class, 'caseInsensitive' => true, 'on' => [self::SCENARIO_DEFAULT, self::SCENARIO_LIVE]];
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
    public function getCrumbs(): array
    {
        return [
            [
                'label' => Craft::t('campaign', 'Contacts'),
                'url' => UrlHelper::url('campaign/contacts'),
            ],
        ];
    }

    /**
     * @inheritdoc
     * @since 2.0.0
     */
    public function getFieldLayout(): ?FieldLayout
    {
        $fieldLayout = Craft::$app->getFields()->getLayoutByType(self::class);

        if (!$this->getIsDraft()) {
            $fieldLayout->setTabs(array_merge(
                $fieldLayout->getTabs(),
                [
                    new ContactMailingListFieldLayoutTab(),
                    new ContactReportFieldLayoutTab(),
                ],
            ));
        }

        return $fieldLayout;
    }

    /**
     * @inheritdoc
     * @since 2.0.0
     */
    protected function metadata(): array
    {
        $formatter = Craft::$app->getFormatter();
        $metadata = parent::metadata();

        $metadata[Craft::t('campaign', 'CID')] = '<code>' . $this->cid . '</code>';

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
     */
    public function getSupportedSites(): array
    {
        return Craft::$app->getSites()->getAllSiteIds();
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

        return Craft::$app->getUsers()->getUserByUsernameOrEmail($this->email);
    }

    /**
     * Checks whether the contact is subscribed to at least one mailing list.
     */
    public function getIsSubscribed(): bool
    {
        $count = ContactMailingListRecord::find()
            ->where([
                'contactId' => $this->id,
                'subscriptionStatus' => 'subscribed',
            ])
            ->limit(1)
            ->count();

        return $count > 0;
    }

    /**
     * Returns the unsubscribe URL.
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
            ->select('subscriptionStatus')
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
        return $this->_getMailingListCount('subscribed');
    }

    /**
     * Returns the number of unsubscribed mailing lists.
     */
    public function getUnsubscribedCount(): int
    {
        return $this->_getMailingListCount('unsubscribed');
    }

    /**
     * Returns the number of complained mailing lists.
     */
    public function getComplainedCount(): int
    {
        return $this->_getMailingListCount('complained');
    }

    /**
     * Returns the number of bounced mailing lists.
     */
    public function getBouncedCount(): int
    {
        return $this->_getMailingListCount('bounced');
    }

    /**
     * Returns the subscribed mailing lists.
     */
    public function getSubscribedMailingLists(): array
    {
        return $this->_getMailingLists('subscribed');
    }

    /**
     * Returns the subscribed mailing lists.
     */
    public function getUnsubscribedMailingLists(): array
    {
        return $this->_getMailingLists('unsubscribed');
    }

    /**
     * Returns the complained mailing lists.
     */
    public function getComplainedMailingLists(): array
    {
        return $this->_getMailingLists('complained');
    }

    /**
     * Returns the bounced mailing lists.
     */
    public function getBouncedMailingLists(): array
    {
        return $this->_getMailingLists('bounced');
    }

    /**
     * Returns all mailing lists that this contact is in.
     */
    public function getMailingLists(): array
    {
        return $this->_getMailingLists();
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
     * @since 2.0.0
     */
    protected function cpEditUrl(): ?string
    {
        return UrlHelper::cpUrl('campaign/contacts/' . $this->id);
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

        // Set the live scenario
        //$this->setScenario(Element::SCENARIO_LIVE);

        return parent::beforeSave($isNew);
    }

    /**
     * @inheritdoc
     */
    public function afterSave(bool $isNew): void
    {
        if ($isNew) {
            $contactRecord = new ContactRecord();
            $contactRecord->id = $this->id;
            $contactRecord->userId = $this->userId;
            $contactRecord->cid = $this->cid;
        }
        else {
            $contactRecord = ContactRecord::findOne($this->id);
        }

        if ($contactRecord) {
            // Set attributes
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
    private function _getMailingListCount(string $subscriptionStatus = null): int
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
     * Returns the mailing lists that this contact is in.
     *
     * @return MailingListElement[]
     */
    private function _getMailingLists(string $subscriptionStatus = null): array
    {
        $subscriptionStatus = $subscriptionStatus ?? '';

        $mailingLists = [];

        $condition = ['contactId' => $this->id];

        if ($subscriptionStatus) {
            $condition['subscriptionStatus'] = $subscriptionStatus;
        }

        /** @var ContactMailingListRecord[] $contactMailingLists */
        $contactMailingLists = ContactMailingListRecord::find()
            ->select('mailingListId')
            ->where($condition)
            ->all();

        foreach ($contactMailingLists as $contactMailingList) {
            $mailingList = Campaign::$plugin->mailingLists->getMailingListById($contactMailingList->mailingListId);

            if ($mailingList) {
                $mailingLists[] = $mailingList;
            }
        }

        return $mailingLists;
    }
}
