<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\elements;

use Craft;
use craft\base\Element;
use craft\elements\actions\Delete;
use craft\elements\actions\Duplicate;
use craft\elements\actions\Restore;
use craft\elements\User;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\validators\DateTimeValidator;
use craft\web\CpScreenResponseBehavior;
use DateTime;
use putyourlightson\campaign\base\ScheduleModel;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\actions\CancelSendouts;
use putyourlightson\campaign\elements\actions\EditSendout;
use putyourlightson\campaign\elements\actions\PauseSendouts;
use putyourlightson\campaign\elements\db\SendoutElementQuery;
use putyourlightson\campaign\fieldlayoutelements\sendouts\SendoutFieldLayoutTab;
use putyourlightson\campaign\helpers\SendoutHelper;
use putyourlightson\campaign\helpers\StringHelper;
use putyourlightson\campaign\models\AutomatedScheduleModel;
use putyourlightson\campaign\models\RecurringScheduleModel;
use putyourlightson\campaign\records\SendoutRecord;
use putyourlightson\campaign\validators\SendoutCampaignValidator;
use putyourlightson\campaign\validators\SendoutContactsValidator;
use putyourlightson\campaign\validators\SendoutExcludedMailingListsValidator;
use putyourlightson\campaign\validators\SendoutMailingListsValidator;
use putyourlightson\campaign\validators\SendoutSegmentsValidator;
use yii\web\Response;

/**
 * @property ScheduleModel|array|string|null $schedule
 * @property-write array $campaignIds
 * @property-read null|string $cpPreviewUrl
 * @property-read bool $isResumable
 * @property-read array $pendingRecipients
 * @property-read string $sendoutTypeLabel
 * @property-read float $progressFraction
 * @property-read bool $isModifiable
 * @property-read int $segmentCount
 * @property-read SegmentElement[] $segments
 * @property-read int $pendingRecipientCount
 * @property-read bool $isDeletable
 * @property-read int $contactCount
 * @property-read bool $isPausable
 * @property-read int $excludedMailingListCount
 * @property-read null|string $postEditUrl
 * @property-read int $mailingListCount
 * @property-read bool $canSendNow
 * @property-read MailingListElement[] $excludedMailingLists
 * @property-read string $fromNameEmailLabel
 * @property-read bool $isSendable
 * @property-read User|null $sender
 * @property-read array[] $crumbs
 * @property-read bool $isCancellable
 * @property-read null|CampaignElement $campaign
 * @property-read string $progress
 * @property-read string[] $notificationEmailAddresses
 * @property-read ContactElement[] $notificationContacts
 * @property-read ContactElement[] $contacts
 * @property-read ContactElement[] $failedContacts
 * @property-read MailingListElement[] $mailingLists
 */
class SendoutElement extends Element
{
    /**
     * @const string
     */
    public const STATUS_SENT = 'sent';
    /**
     * @const string
     */
    public const STATUS_SENDING = 'sending';
    /**
     * @const string
     */
    public const STATUS_QUEUED = 'queued';
    /**
     * @const string
     */
    public const STATUS_PENDING = 'pending';
    /**
     * @const string
     */
    public const STATUS_PAUSED = 'paused';
    /**
     * @const string
     */
    public const STATUS_CANCELLED = 'cancelled';
    /**
     * @const string
     */
    public const STATUS_FAILED = 'failed';
    /**
     * @const string
     */
    public const STATUS_DRAFT = 'draft';

    /**
     * Returns the sendout types.
     */
    public static function sendoutTypes(): array
    {
        $sendoutTypes = [
            'regular' => Craft::t('campaign', 'Regular'),
            'scheduled' => Craft::t('campaign', 'Scheduled'),
        ];

        if (Campaign::$plugin->getIsPro()) {
            $sendoutTypes['automated'] = Craft::t('campaign', 'Automated');
            $sendoutTypes['recurring'] = Craft::t('campaign', 'Recurring');
            $sendoutTypes['singular'] = Craft::t('campaign', 'Singular');
        }

        return $sendoutTypes;
    }

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('campaign', 'Sendout');
    }

    /**
     * @inheritdoc
     */
    public static function lowerDisplayName(): string
    {
        return Craft::t('campaign', 'sendout');
    }

    /**
     * @inheritdoc
     */
    public static function pluralDisplayName(): string
    {
        return Craft::t('campaign', 'Sendouts');
    }

    /**
     * @inheritdoc
     */
    public static function pluralLowerDisplayName(): string
    {
        return Craft::t('campaign', 'sendouts');
    }

    /**
     * @inheritdoc
     */
    public static function refHandle(): ?string
    {
        return 'sendout';
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
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_SENT => Craft::t('campaign', 'Sent'),
            self::STATUS_SENDING => Craft::t('campaign', 'Sending'),
            self::STATUS_QUEUED => Craft::t('campaign', 'Queued'),
            self::STATUS_PENDING => Craft::t('campaign', 'Pending'),
            self::STATUS_PAUSED => Craft::t('campaign', 'Paused'),
            self::STATUS_CANCELLED => Craft::t('campaign', 'Cancelled'),
            self::STATUS_FAILED => Craft::t('campaign', 'Failed'),
            self::STATUS_DRAFT => Craft::t('campaign', 'Draft'),
        ];
    }

    /**
     * @inheritdoc
     */
    public static function find(): SendoutElementQuery
    {
        $elementQuery = new SendoutElementQuery(static::class);

        // Limit sendout types
        $elementQuery->sendoutType = array_keys(self::sendoutTypes());

        return $elementQuery;
    }

    /**
     * @inheritdoc
     */
    protected static function defineSources(string $context): array
    {
        $sources = [
            [
                'key' => '*',
                'label' => Craft::t('campaign', 'All sendouts'),
                'criteria' => [],
                'defaultSort' => ['lastSent', 'desc'],
            ],
            [
                'heading' => Craft::t('campaign', 'Sendout Types'),
            ],
        ];
        $sendoutTypes = self::sendoutTypes();

        foreach ($sendoutTypes as $sendoutType => $label) {
            $sources[] = [
                'key' => 'sendoutType:' . $sendoutType,
                'label' => $label,
                'data' => ['handle' => $sendoutType],
                'criteria' => ['sendoutType' => $sendoutType],
                'defaultSort' => ['lastSent', 'desc'],
            ];
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
            'type' => EditSendout::class,
        ]);

        // Duplicate
        $actions[] = $elementsService->createAction([
            'type' => Duplicate::class,
        ]);

        // Pause
        $actions[] = PauseSendouts::class;

        // Cancel
        $actions[] = CancelSendouts::class;

        // Delete
        $actions[] = $elementsService->createAction([
            'type' => Delete::class,
            'confirmationMessage' => Craft::t('campaign', 'Are you sure you want to delete the selected sendouts?'),
            'successMessage' => Craft::t('campaign', 'Sendouts deleted.'),
        ]);

        // Restore
        $actions[] = $elementsService->createAction([
            'type' => Restore::class,
            'successMessage' => Craft::t('campaign', 'Sendouts restored.'),
            'partialSuccessMessage' => Craft::t('campaign', 'Some sendouts restored.'),
            'failMessage' => Craft::t('campaign', 'Sendouts not restored.'),
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
            'subject' => Craft::t('campaign', 'Subject'),
            'recipients' => Craft::t('campaign', 'Recipients'),
            'sendDate' => Craft::t('campaign', 'Send Date'),
            'lastSent' => Craft::t('campaign', 'Last Sent'),
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
        $attributes = [
            'sendoutType' => ['label' => Craft::t('campaign', 'Sendout Type')],
            'subject' => ['label' => Craft::t('campaign', 'Subject')],
            'campaignId' => ['label' => Craft::t('campaign', 'Campaign')],
            'recipients' => ['label' => Craft::t('campaign', 'Recipients')],
            'failures' => ['label' => Craft::t('campaign', 'Failures')],
            'progress' => ['label' => Craft::t('campaign', 'Progress')],
            'sender' => ['label' => Craft::t('campaign', 'Sent By')],
            'mailingListIds' => ['label' => Craft::t('campaign', 'Mailing Lists')],
            'sendDate' => ['label' => Craft::t('campaign', 'Send Date')],
            'lastSent' => ['label' => Craft::t('campaign', 'Last Sent')],
            'dateCreated' => ['label' => Craft::t('app', 'Date Created')],
            'dateUpdated' => ['label' => Craft::t('app', 'Date Updated')],
        ];

        // Hide sender from Craft Personal/Client
        if (Craft::$app->getEdition() !== Craft::Pro) {
            unset($attributes['sender']);
        }

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    protected static function defineDefaultTableAttributes(string $source): array
    {
        if ($source == '*') {
            return ['sendoutType', 'campaignId', 'recipients', 'lastSent', 'progress'];
        }

        $sendoutType = str_replace('sendoutType:', '', $source);

        if ($sendoutType == 'regular' || $sendoutType == 'scheduled' || $sendoutType == 'singular') {
            return ['campaignId', 'recipients', 'sendDate', 'lastSent', 'progress'];
        }

        return ['campaignId', 'recipients', 'lastSent'];
    }

    /**
     * @inheritdoc
     */
    protected static function defineSearchableAttributes(): array
    {
        return ['title', 'sid', 'subject', 'fromName'];
    }

    /**
     * @var string|null SID
     */
    public ?string $sid = null;

    /**
     * @var int|null Campaign ID
     * @see setCampaignIds()
     */
    public ?int $campaignId = null;

    /**
     * @var int|null Sender ID
     */
    public ?int $senderId = null;

    /**
     * @var string|null Sendout type
     */
    public ?string $sendoutType = null;

    /**
     * @var string Send status
     */
    public string $sendStatus = self::STATUS_DRAFT;

    /**
     * @var string|null Send from name/email/reply combo, used for posted params
     * @see SendoutElement::beforeSave()
     */
    public ?string $fromNameEmail = null;

    /**
     * @var string|null Send from name
     */
    public ?string $fromName = null;

    /**
     * @var string|null Send from email
     */
    public ?string $fromEmail = null;

    /**
     * @var string|null Reply to email
     */
    public ?string $replyToEmail = null;

    /**
     * @var string|null Email subject
     */
    public ?string $subject = null;

    /**
     * @var array|null Notification contact IDs
     */
    public ?array $notificationContactIds = null;

    /**
     * @var array|null Contact IDs
     */
    public ?array $contactIds = null;

    /**
     * @var array|null Failed contact IDs
     */
    public ?array $failedContactIds = null;

    /**
     * @var array|null Mailing list IDs
     */
    public ?array $mailingListIds = null;

    /**
     * @var array|null Excluded mailing list IDs
     */
    public ?array $excludedMailingListIds = null;

    /**
     * @var array|null Segment IDs
     */
    public ?array $segmentIds = null;

    /**
     * @var int Recipients
     */
    public int $recipients = 0;

    /**
     * @var int Failures
     */
    public int $failures = 0;

    /**
     * @var string|null HTML body
     */
    public ?string $htmlBody = null;

    /**
     * @var string|null Plaintext body
     */
    public ?string $plaintextBody = null;

    /**
     * @var DateTime|null Send date
     */
    public ?DateTime $sendDate = null;

    /**
     * @var DateTime|null Last sent
     */
    public ?DateTime $lastSent = null;

    /**
     * @var CampaignElement|null
     */
    private ?CampaignElement $_campaign = null;

    /**
     * @var User|null
     */
    private ?User $_sender = null;

    /**
     * @var ContactElement[]|null
     */
    private ?array $_contacts = null;

    /**
     * @var ContactElement[]|null
     */
    private ?array $_failedContacts = null;

    /**
     * @var MailingListElement[]|null
     */
    private ?array $_mailingLists = null;

    /**
     * @var MailingListElement[]|null
     */
    private ?array $_excludedMailingLists = null;

    /**
     * @var SegmentElement[]|null
     */
    private ?array $_segments = null;

    /**
     * @var array|null
     */
    private ?array $_pendingRecipients = null;

    /**
     * @var ScheduleModel|null Schedule
     * @see getSchedule()
     * @see setSchedule()
     */
    private ?ScheduleModel $_schedule = null;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        $this->title = SendoutHelper::decodeEmojis($this->title);
        $this->subject = SendoutHelper::decodeEmojis($this->subject);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        $labels = parent::attributeLabels();

        // Set the field labels
        $labels['campaignId'] = Craft::t('campaign', 'Campaign');
        $labels['mailingListIds'] = Craft::t('campaign', 'Mailing lists');
        $labels['excludedMailingListIds'] = Craft::t('campaign', 'Excluded mailing lists');
        $labels['contactIds'] = Craft::t('campaign', 'Contacts');
        $labels['segmentIds'] = Craft::t('campaign', 'Segments');

        return $labels;
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['sendoutType'], 'required'];
        $rules[] = [['fromName', 'fromEmail', 'subject'], 'required', 'on' => [self::SCENARIO_DEFAULT, self::SCENARIO_LIVE]];
        $rules[] = [['campaignId'], SendoutCampaignValidator::class, 'on' => [self::SCENARIO_DEFAULT, self::SCENARIO_LIVE]];
        $rules[] = [['mailingListIds'], SendoutMailingListsValidator::class, 'on' => [self::SCENARIO_DEFAULT, self::SCENARIO_LIVE], 'when' => fn(SendoutElement $element) => $element->sendoutType != 'singular'];
        $rules[] = [['excludedMailingListIds'], SendoutExcludedMailingListsValidator::class, 'on' => [self::SCENARIO_DEFAULT, self::SCENARIO_LIVE], 'when' => fn(SendoutElement $element) => $element->sendoutType != 'singular'];
        $rules[] = [['contactIds'], SendoutContactsValidator::class, 'on' => [self::SCENARIO_DEFAULT, self::SCENARIO_LIVE], 'when' => fn(SendoutElement $element) => $element->sendoutType == 'singular'];
        $rules[] = [['segmentIds'], SendoutSegmentsValidator::class, 'on' => [self::SCENARIO_DEFAULT, self::SCENARIO_LIVE]];
        $rules[] = [['recipients', 'campaignId', 'senderId'], 'number', 'integerOnly' => true];
        $rules[] = [['sid'], 'string', 'max' => 17];
        $rules[] = [['fromName', 'fromEmail', 'subject'], 'string', 'max' => 255];
        $rules[] = [['sendDate'], DateTimeValidator::class];

        // Safe rules
        $rules[] = [['notificationContactIds', 'campaignIds', 'failedContactIds', 'fromNameEmail', 'schedule'], 'safe'];

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getPostEditUrl(): ?string
    {
        return UrlHelper::cpUrl("campaign/sendouts");
    }

    /**
     * @inheritdoc
     * @since 2.0.0
     */
    public function prepareEditScreen(Response $response, string $containerId): void
    {
        Craft::$app->getView()->registerJs('new Campaign.SendoutEdit(\'' . $containerId . '\');');

        /** @var Response|CpScreenResponseBehavior $response */
        if (!$this->getIsModifiable()) {
            // Only redirect if we're not already redirecting, to prevent an endless loop in Craft 4.0.4 and above.
            // https://github.com/putyourlightson/craft-campaign/issues/316
            if (!$response->getIsRedirection()) {
                $response->redirect($this->getCpPreviewUrl());
            }
        }

        $response->selectedSubnavItem = 'sendouts';

        $response->crumbs([
            [
                'label' => Craft::t('campaign', 'Sendouts'),
                'url' => UrlHelper::url('campaign/sendouts'),
            ],
            [
                'label' => $this->getSendoutTypeLabel(),
                'url' => UrlHelper::url('campaign/sendouts/' . $this->sendoutType),
            ],
        ]);

        $response->submitButtonLabel = Craft::t('campaign', 'Save and Preview');
        $response->redirectUrl = $this->getCpPreviewUrl();
    }

    /**
     * Returns the sendout’s preview URL in the control panel.
     *
     * @since 2.0.0
     */
    public function getCpPreviewUrl(): ?string
    {
        $path = sprintf('campaign/sendouts/%s/%s/%s', $this->sendoutType, 'preview', $this->getCanonicalId());

        return UrlHelper::cpUrl($path);
    }

    /**
     * @inheritdoc
     * @since 2.0.0
     */
    protected function cpEditUrl(): ?string
    {
        $path = sprintf('campaign/sendouts/%s/%s', $this->sendoutType, $this->getCanonicalId());

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
            new SendoutFieldLayoutTab(),
        ]);

        return $fieldLayout;
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

        return $user->can('campaign:sendouts');
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

        return $user->can('campaign:sendouts');
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

        return $user->can('campaign:sendouts');
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
     * Returns the sendout type label for the given sendout type.
     */
    public function getSendoutTypeLabel(): string
    {
        $sendoutTypes = self::sendoutTypes();

        return $sendoutTypes[$this->sendoutType];
    }

    /**
     * Returns the from name, email and reply to.
     */
    public function getFromNameEmail(): string
    {
        return $this->fromName ? $this->fromName . ':' . $this->fromEmail . ':' . $this->replyToEmail : '';
    }

    /**
     * Returns the from name, email and reply to label.
     */
    public function getFromNameEmailLabel(): string
    {
        $label = $this->fromName ? $this->fromName . ' <' . $this->fromEmail . '> ' : '';

        if ($this->replyToEmail) {
            $label .= Craft::t('campaign', '(reply to {email})', ['email' => $this->replyToEmail]);
        }

        return $label;
    }

    /**
     * Returns the sendout's progress as a fraction.
     */
    public function getProgressFraction(): float
    {
        if ($this->sendStatus == self::STATUS_SENT) {
            return 1;
        }

        // Get expected recipients
        $expectedRecipients = $this->getPendingRecipientCount();

        $progress = $expectedRecipients == 0 ?: $this->recipients / ($this->recipients + $expectedRecipients);

        return $progress < 1 ? $progress : 1;
    }

    /**
     * Returns the sendout's progress.
     */
    public function getProgress(): string
    {
        if ($this->sendStatus == self::STATUS_DRAFT || $this->sendoutType == 'automated' || $this->sendoutType == 'recurring') {
            return '';
        }

        $progress = round(100 * $this->getProgressFraction());

        return $progress . '%';
    }

    /**
     * Returns the sendout's campaign.
     */
    public function getCampaign(): ?CampaignElement
    {
        if ($this->campaignId === null) {
            return null;
        }

        if ($this->_campaign !== null) {
            return $this->_campaign;
        }

        $this->_campaign = Campaign::$plugin->campaigns->getCampaignById($this->campaignId);

        return $this->_campaign;
    }

    /**
     * Returns the sender.
     */
    public function getSender(): ?User
    {
        if ($this->senderId === null) {
            return null;
        }

        if ($this->_sender !== null) {
            return $this->_sender;
        }

        $this->_sender = Craft::$app->getUsers()->getUserById($this->senderId);

        return $this->_sender;
    }

    /**
     * Returns the sendout's notification email addresses.
     *
     * @return string[]
     */
    public function getNotificationEmailAddresses(): array
    {
        if (empty($this->notificationContactIds)) {
            return [];
        }

        return ContactElement::find()
            ->select('email')
            ->id($this->notificationContactIds)
            ->column();
    }

    /**
     * Returns the sendout's notification contacts, or the default notification
     * contacts if this is a fresh sendout.
     *
     * @return ContactElement[]
     */
    public function getNotificationContacts(): array
    {
        if ($this->getIsFresh()) {
            return Campaign::$plugin->settings->getDefaultNotificationContacts();
        }

        if (empty($this->notificationContactIds)) {
            return [];
        }

        return Campaign::$plugin->contacts->getContactsByIds($this->notificationContactIds);
    }

    /**
     * Returns the sendout's contact count.
     */
    public function getContactCount(): int
    {
        return count($this->contactIds);
    }

    /**
     * Returns the sendout's contacts.
     *
     * @return ContactElement[]
     */
    public function getContacts(): array
    {
        if ($this->_contacts !== null) {
            return $this->_contacts;
        }

        $this->_contacts = Campaign::$plugin->contacts->getContactsByIds($this->contactIds);

        return $this->_contacts;
    }

    /**
     * Returns the sendout's failed contacts.
     *
     * @return ContactElement[]
     */
    public function getFailedContacts(): array
    {
        if ($this->_failedContacts !== null) {
            return $this->_failedContacts;
        }

        $this->_failedContacts = Campaign::$plugin->contacts->getContactsByIds($this->failedContactIds);

        return $this->_failedContacts;
    }

    /**
     * Returns the sendout's mailing list count.
     */
    public function getMailingListCount(): int
    {
        return count($this->mailingListIds);
    }

    /**
     * Returns the sendout's mailing lists.
     *
     * @return MailingListElement[]
     */
    public function getMailingLists(): array
    {
        if ($this->_mailingLists !== null) {
            return $this->_mailingLists;
        }

        $this->_mailingLists = Campaign::$plugin->mailingLists->getMailingListsByIds($this->mailingListIds);

        return $this->_mailingLists;
    }

    /**
     * Returns the sendout's excluded mailing list count.
     */
    public function getExcludedMailingListCount(): int
    {
        return count($this->excludedMailingListIds);
    }

    /**
     * Returns the sendout's excluded mailing lists.
     *
     * @return MailingListElement[]
     */
    public function getExcludedMailingLists(): array
    {
        if ($this->_excludedMailingLists !== null) {
            return $this->_excludedMailingLists;
        }

        $this->_excludedMailingLists = Campaign::$plugin->mailingLists->getMailingListsByIds($this->excludedMailingListIds);

        return $this->_excludedMailingLists;
    }

    /**
     * Returns the sendout's segment count.
     */
    public function getSegmentCount(): int
    {
        return count($this->segmentIds);
    }

    /**
     * Returns the sendout's segments.
     *
     * @return SegmentElement[]
     */
    public function getSegments(): array
    {
        if (!Campaign::$plugin->getIsPro()) {
            return [];
        }

        if ($this->_segments !== null) {
            return $this->_segments;
        }

        $this->_segments = Campaign::$plugin->segments->getSegmentsByIds($this->segmentIds);

        return $this->_segments;
    }

    /**
     * Returns the sendout's schedule.
     *
     * @return ScheduleModel|null
     */
    public function getSchedule(): ScheduleModel|null
    {
        if ($this->_schedule !== null) {
            return $this->_schedule;
        }

        if ($this->sendoutType == 'automated') {
            $this->_schedule = new AutomatedScheduleModel();
        } elseif ($this->sendoutType == 'recurring') {
            $this->_schedule = new RecurringScheduleModel();
        }

        return $this->_schedule;
    }

    /**
     * Returns the sendout's pending recipient contact and mailing list IDs based
     * on its mailing lists, segments and schedule.
     */
    public function getPendingRecipients(): array
    {
        if ($this->_pendingRecipients !== null) {
            return $this->_pendingRecipients;
        }

        $this->_pendingRecipients = Campaign::$plugin->sendouts->getPendingRecipients($this);

        return $this->_pendingRecipients;
    }

    /**
     * Returns the sendout's pending recipient count
     */
    public function getPendingRecipientCount(): int
    {
        if ($this->_pendingRecipients !== null) {
            return count($this->_pendingRecipients);
        }

        return Campaign::$plugin->sendouts->getPendingRecipientCount($this);
    }

    /**
     * Returns the sendout's HTML body.
     */
    public function getHtmlBody(): ?string
    {
        if ($this->sendStatus == self::STATUS_SENT) {
            return $this->htmlBody;
        }

        $campaign = $this->getCampaign();
        if ($campaign === null) {
            return '';
        }

        $contact = new ContactElement();

        return $campaign->getHtmlBody($contact, $this);
    }

    /**
     * Returns the sendout's plaintext body.
     */
    public function getPlaintextBody(): ?string
    {
        if ($this->sendStatus == self::STATUS_SENT) {
            return $this->plaintextBody;
        }

        $campaign = $this->getCampaign();
        if ($campaign === null) {
            return '';
        }

        $contact = new ContactElement();

        return $campaign->getPlaintextBody($contact, $this);
    }

    /**
     * @inheritdoc
     */
    public function getSearchKeywords(string $attribute): string
    {
        if ($attribute == 'title' || $attribute == 'subject') {
            return SendoutHelper::encodeEmojis($this->{$attribute});
        }

        return parent::getSearchKeywords($attribute);
    }

    /**
     * @inheritdoc
     */
    public function getStatus(): ?string
    {
        return $this->sendStatus;
    }

    /**
     * Returns whether the sendout can be modified.
     * https://github.com/putyourlightson/craft-campaign/issues/161
     */
    public function getIsModifiable(): bool
    {
        return ($this->getStatus() == self::STATUS_DRAFT || $this->getStatus() == self::STATUS_PAUSED);
    }

    /**
     * Returns whether the sendout can be sent now
     */
    public function getCanSendNow(): bool
    {
        if ($this->sendoutType == 'automated' || $this->sendoutType == 'recurring') {
            return $this->getSchedule()->canSendNow($this);
        }

        return true;
    }

    /**
     * Returns whether the sendout is sendable.
     */
    public function getIsSendable(): bool
    {
        return ($this->getStatus() == self::STATUS_SENDING || $this->getStatus() == self::STATUS_QUEUED);
    }

    /**
     * Returns whether the sendout is pausable.
     */
    public function getIsPausable(): bool
    {
        return ($this->getStatus() == self::STATUS_SENDING || $this->getStatus() == self::STATUS_QUEUED || $this->getStatus() == self::STATUS_PENDING || $this->getStatus() == self::STATUS_FAILED);
    }

    /**
     * Returns whether the sendout is resumable.
     */
    public function getIsResumable(): bool
    {
        return ($this->getStatus() == self::STATUS_PAUSED || $this->getStatus() == self::STATUS_FAILED);
    }

    /**
     * Returns whether the sendout is cancellable.
     */
    public function getIsCancellable(): bool
    {
        return ($this->getStatus() != self::STATUS_DRAFT && $this->getStatus() != self::STATUS_CANCELLED && $this->getStatus() != self::STATUS_SENT);
    }

    /**
     * Returns whether the sendout is deletable.
     */
    public function getIsDeletable(): bool
    {
        return (!$this->getIsPausable() || $this->getStatus() == self::STATUS_FAILED);
    }

    /**
     * Sets the campaign IDs.
     */
    public function setCampaignIds(array|string $campaignIds): void
    {
        $this->campaignId = $campaignIds[0] ?? null;
    }

    /**
     * Sets the sendout's schedule.
     */
    public function setSchedule(ScheduleModel|array|string|null $schedule): void
    {
        if ($this->sendoutType != 'automated' && $this->sendoutType != 'recurring') {
            return;
        }

        if (is_string($schedule)) {
            $schedule = Json::decodeIfJson($schedule);
        }

        if ($schedule instanceof ScheduleModel) {
            $this->_schedule = $schedule;
        } else {
            $this->_schedule = $this->getSchedule();
            $this->_schedule->setAttributes($schedule ?: []);
        }
    }

    /**
     * @inheritdoc
     */
    protected function htmlAttributes(string $context): array
    {
        $htmlAttributes = [];

        if ($this->getIsModifiable()) {
            $htmlAttributes['data-modifiable'] = true;
        }

        if ($this->getIsPausable()) {
            $htmlAttributes['data-pausable'] = true;
        }

        if ($this->getIsResumable()) {
            $htmlAttributes['data-resumable'] = true;
        }

        if ($this->getIsCancellable()) {
            $htmlAttributes['data-cancellable'] = true;
        }

        return $htmlAttributes;
    }

    /**
     * @inheritdoc
     */
    protected function tableAttributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'sendoutType':
                return $this->getSendoutTypeLabel();

            case 'campaignId':
                $campaign = $this->getCampaign();
                return $campaign ? Craft::$app->getView()->renderTemplate('_elements/element', ['element' => $campaign]) : '';

            case 'sender':
                $sender = $this->getSender();
                return $sender ? Craft::$app->getView()->renderTemplate('_elements/element', ['element' => $sender]) : '';

            case 'mailingListIds':
                return (string)$this->getMailingListCount();
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
            $this->sid = StringHelper::uniqueId('s');
        }

        // Reset stats if this is a duplicate of a non-draft sendout.
        if ($this->firstSave && $this->duplicateOf !== null) {
            $this->senderId = null;
            $this->sendStatus = self::STATUS_DRAFT;
            $this->recipients = 0;
            $this->failures = 0;
            $this->failedContactIds = null;
            $this->sendDate = null;
            $this->lastSent = null;
        }

        $this->title = SendoutHelper::encodeEmojis($this->title);
        $this->subject = SendoutHelper::encodeEmojis($this->subject);

        if (Campaign::$plugin->settings->showSendoutTitleField === false) {
            $this->title = $this->subject;
        }

        // Get from name and email if submitted
        if ($this->fromNameEmail) {
            $fromNameEmail = explode(':', $this->fromNameEmail);
            $this->fromName = $fromNameEmail[0] ?? '';
            $this->fromEmail = $fromNameEmail[1] ?? '';
            $this->replyToEmail = $fromNameEmail[2] ?? '';
        }

        // Convert send date
        $this->sendDate = DateTimeHelper::toDateTime($this->sendDate) ?: new DateTime();

        if ($this->sendoutType == 'automated' || $this->sendoutType == 'recurring') {
            // Validate schedule
            if ($this->getScenario() != self::SCENARIO_ESSENTIALS) {
                if ($this->getSchedule() === null || !$this->getSchedule()->validate()) {
                    return false;
                }
            }
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
                $sendoutRecord = new SendoutRecord();
                $sendoutRecord->id = $this->id;
            } else {
                $sendoutRecord = SendoutRecord::findOne($this->id);
            }

            $sendoutRecord->setAttributes($this->getAttributes(), false);

            $schedule = $this->getSchedule();
            $sendoutRecord->schedule = $schedule ? $schedule->getAttributes() : null;

            $sendoutRecord->save(false);
        }

        parent::afterSave($isNew);
    }
}
