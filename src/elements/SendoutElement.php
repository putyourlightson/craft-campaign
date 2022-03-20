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
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\validators\DateTimeValidator;
use DateTime;
use LitEmoji\LitEmoji;
use putyourlightson\campaign\base\ScheduleModel;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\actions\CancelSendouts;
use putyourlightson\campaign\elements\actions\PauseSendouts;
use putyourlightson\campaign\elements\db\SendoutElementQuery;
use putyourlightson\campaign\fieldlayoutelements\sendouts\SendoutFieldLayoutTab;
use putyourlightson\campaign\helpers\StringHelper;
use putyourlightson\campaign\models\AutomatedScheduleModel;
use putyourlightson\campaign\models\RecurringScheduleModel;
use putyourlightson\campaign\records\SendoutRecord;

/**
 * @property-read bool $isResumable
 * @property-read array $pendingRecipients
 * @property-read string $sendoutTypeLabel
 * @property-read float $progressFraction
 * @property-read bool $isModifiable
 * @property-read int $segmentCount
 * @property-read SegmentElement[] $segments
 * @property-read int $pendingRecipientCount
 * @property-read bool $isDeletable
 * @property-read bool $isPausable
 * @property-read bool $isEditable
 * @property-read int $excludedMailingListCount
 * @property-read int $mailingListCount
 * @property-read bool $canSendNow
 * @property-read MailingListElement[] $excludedMailingLists
 * @property-read string $fromNameEmailLabel
 * @property-read bool $isSendable
 * @property-read User|null $sender
 * @property-read bool $isCancellable
 * @property-read null|CampaignElement $campaign
 * @property-read string $progress
 * @property-read array[] $crumbs
 * @property-read null|string $postEditUrl
 * @property-read null|string $cpPreviewUrl
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
    protected static function defineSources(string $context = null): array
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
        $id = 1;

        foreach ($sendoutTypes as $sendoutType => $label) {
            $sources[] = [
                'key' => 'sendoutTypeId:' . $id,
                'label' => $label,
                'data' => ['handle' => $sendoutType],
                'criteria' => ['sendoutType' => $sendoutType],
                'defaultSort' => ['lastSent', 'desc'],
            ];

            $id++;
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
            'label' => Craft::t('campaign', 'Edit sendout'),
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
            'title' => ['label' => Craft::t('app', 'Title')],
            'sendoutType' => ['label' => Craft::t('campaign', 'Sendout Type')],
            'subject' => ['label' => Craft::t('campaign', 'Subject')],
            'campaignId' => ['label' => Craft::t('campaign', 'Campaign')],
            'recipients' => ['label' => Craft::t('campaign', 'Recipients')],
            'fails' => ['label' => Craft::t('campaign', 'Fails')],
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
            $attributes = ['title', 'sendoutType', 'campaignId', 'recipients', 'lastSent', 'progress'];
        }
        elseif ($source == 'regular' || $source == 'scheduled') {
            $attributes = ['title', 'campaignId', 'recipients', 'sendDate', 'lastSent', 'progress'];
        }
        else {
            $attributes = ['title', 'campaignId', 'recipients', 'lastSent'];
        }

        return $attributes;
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
     * @var array|null Campaign IDs, used for posted params
     * @see SendoutElement::beforeSave()
     */
    public ?array $campaignIds = null;

    /**
     * @var int|null Campaign ID
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
     * @var string|null Notification email address
     */
    public ?string $notificationEmailAddress = null;

    /**
     * @var array|string|null Mailing list IDs
     */
    public array|string|null $mailingListIds = null;

    /**
     * @var array|string|null Excluded mailing list IDs
     */
    public array|string|null $excludedMailingListIds = null;

    /**
     * @var array|string|null Segment IDs
     */
    public array|string|null $segmentIds = null;

    /**
     * @var int Recipients
     */
    public int $recipients = 0;

    /**
     * @var int Fails
     */
    public int $fails = 0;

    /**
     * @var array|string|null Schedule
     */
    public array|string|null $schedule = null;

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
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        if (Craft::$app->getDb()->getIsMysql()) {
            // Decode subject for emojis
            $this->subject = LitEmoji::shortcodeToUnicode($this->subject);
        }
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
        $labels['segmentIds'] = Craft::t('campaign', 'Segments');

        return $labels;
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['sendoutType', ], 'required'];
        $rules[] = [['fromName', 'fromEmail', 'subject', 'campaignId', 'mailingListIds'], 'required', 'on' => [self::SCENARIO_DEFAULT, self::SCENARIO_LIVE]];
        $rules[] = [['recipients', 'campaignId', 'senderId'], 'number', 'integerOnly' => true];
        $rules[] = [['sid'], 'string', 'max' => 17];
        $rules[] = [['fromName', 'fromEmail', 'subject', 'notificationEmailAddress'], 'string', 'max' => 255];
        $rules[] = [['notificationEmailAddress'], 'email'];
        $rules[] = [['sendDate'], DateTimeValidator::class];

        // Safe rules
        $rules[] = [['campaignIds', 'excludedMailingListIds', 'segmentIds', 'fromNameEmail', 'schedule'], 'safe'];

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
    public function getCrumbs(): array
    {
        return [
            [
                'label' => Craft::t('campaign', 'Sendouts'),
                'url' => UrlHelper::url('campaign/sendouts'),
            ],
            [
                'label' => $this->getSendoutTypeLabel(),
                'url' => UrlHelper::url('campaign/sendouts/' . $this->sendoutType),
            ],
        ];
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
     * Returns the sendout's mailing list IDs.
     *
     * @return int[]
     */
    public function getMailingListIds(): array
    {
        if (is_string($this->mailingListIds)) {
            return StringHelper::split($this->mailingListIds);
        }

        return $this->mailingListIds;
    }

    /**
     * Returns the sendout's mailing list count.
     */
    public function getMailingListCount(): int
    {
        return count($this->getMailingListIds());
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

        $this->_mailingLists = Campaign::$plugin->mailingLists->getMailingListsByIds($this->getMailingListIds());

        return $this->_mailingLists;
    }

    /**
     * Returns the sendout's excluded mailing list IDs.
     *
     * @return int[]
     */
    public function getExcludedMailingListIds(): array
    {
        if (is_string($this->excludedMailingListIds)) {
            return StringHelper::split($this->excludedMailingListIds);
        }

        return $this->excludedMailingListIds;
    }

    /**
     * Returns the sendout's excluded mailing list count.
     */
    public function getExcludedMailingListCount(): int
    {
        return count($this->getExcludedMailingListIds());
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

        $this->_excludedMailingLists = Campaign::$plugin->mailingLists->getMailingListsByIds($this->getExcludedMailingListIds());

        return $this->_excludedMailingLists;
    }

    /**
     * Returns the sendout's segment IDs.
     *
     * @return int[]
     */
    public function getSegmentIds(): array
    {
        if (is_string($this->segmentIds)) {
            return StringHelper::split($this->segmentIds);
        }

        return $this->segmentIds;
    }

    /**
     * Returns the sendout's segment count.
     */
    public function getSegmentCount(): int
    {
        return count($this->getSegmentIds());
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

        $this->_segments = Campaign::$plugin->segments->getSegmentsByIds($this->getSegmentIds());

        return $this->_segments;
    }

    /**
     * Returns the sendout's schedule.
     */
    public function getSchedule(): ?ScheduleModel
    {
        $schedule = null;

        if ($this->sendoutType == 'automated' || $this->sendoutType == 'recurring') {
            $config = is_string($this->schedule) ? Json::decode($this->schedule) : (array)$this->schedule;

            if ($this->sendoutType == 'automated') {
                $schedule = new AutomatedScheduleModel($config ?: []);
            }

            if ($this->sendoutType == 'recurring') {
                $schedule = new RecurringScheduleModel($config ?: []);
            }

            // Convert end date and time of day or set to null
            $schedule->endDate = DateTimeHelper::toDateTime($schedule->endDate) ?: null;
            $schedule->timeOfDay = DateTimeHelper::toDateTime($schedule->timeOfDay) ?: null;
        }

        return $schedule;
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
        return count($this->getPendingRecipients());
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

        return $campaign->getHtmlBody();
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

        return $campaign->getPlaintextBody();
    }

    /**
     * @inheritdoc
     */
    public function getSearchKeywords(string $attribute): string
    {
        if ($attribute == 'subject') {
            return LitEmoji::unicodeToShortcode($this->{$attribute});
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
     * @inheritdoc
     */
    public function getHtmlAttributes(string $context): array
    {
        $htmlAttributes = parent::getHtmlAttributes($context);

        if ($this->getIsPausable()) {
            $htmlAttributes['data-pausable'] = null;
        }

        if ($this->getIsResumable()) {
            $htmlAttributes['data-resumable'] = null;
        }

        if ($this->getIsCancellable()) {
            $htmlAttributes['data-cancellable'] = null;
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

        if (Craft::$app->getDb()->getIsMysql()) {
            // Encode subject for emojis
            $this->subject = LitEmoji::unicodeToShortcode($this->subject);
        }

        // Get from name and email if submitted
        if ($this->fromNameEmail) {
            $fromNameEmail = explode(':', $this->fromNameEmail);
            $this->fromName = $fromNameEmail[0] ?? '';
            $this->fromEmail = $fromNameEmail[1] ?? '';
            $this->replyToEmail = $fromNameEmail[2] ?? '';
        }

        // Get the selected campaign ID
        $this->campaignId = $this->campaignIds[0] ?? $this->campaignId;

        // Get the selected included and excluded mailing list IDs and segment IDs
        $this->mailingListIds = is_array($this->mailingListIds) ? implode(',', $this->mailingListIds) : $this->mailingListIds;
        $this->excludedMailingListIds = is_array($this->excludedMailingListIds) ? implode(',', $this->excludedMailingListIds) : $this->excludedMailingListIds;
        $this->segmentIds = is_array($this->segmentIds) ? implode(',', $this->segmentIds) : $this->segmentIds;

        // Convert send date
        $this->sendDate = DateTimeHelper::toDateTime($this->sendDate) ?: new DateTime();

        if ($this->sendoutType == 'automated' || $this->sendoutType == 'recurring') {
            // Validate schedule
            if ($this->getScenario() != self::SCENARIO_ESSENTIALS) {
                if (!$this->getSchedule()->validate()) {
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
        if ($isNew) {
            $sendoutRecord = new SendoutRecord();
            $sendoutRecord->id = $this->id;
        }
        else {
            $sendoutRecord = SendoutRecord::findOne($this->id);
        }

        if ($sendoutRecord) {
            // Update attributes
            $sendoutRecord->setAttributes($this->getAttributes(), false);
            $sendoutRecord->save(false);
        }

        parent::afterSave($isNew);
    }
}
