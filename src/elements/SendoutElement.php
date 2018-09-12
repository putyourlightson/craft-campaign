<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\elements;

use craft\helpers\Db;
use putyourlightson\campaign\base\ScheduleModel;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\db\SendoutElementQuery;
use putyourlightson\campaign\elements\actions\PauseSendouts;
use putyourlightson\campaign\elements\actions\CancelSendouts;
use putyourlightson\campaign\helpers\StringHelper;
use putyourlightson\campaign\models\AutomatedScheduleModel;
use putyourlightson\campaign\models\RecurringScheduleModel;
use putyourlightson\campaign\records\ContactCampaignRecord;
use putyourlightson\campaign\records\ContactMailingListRecord;
use putyourlightson\campaign\records\SendoutRecord;

use Craft;
use craft\base\Element;
use craft\elements\db\ElementQueryInterface;
use craft\elements\actions\Delete;
use craft\elements\User;
use craft\helpers\DateTimeHelper;
use craft\helpers\UrlHelper;
use craft\helpers\Json;
use craft\validators\DateTimeValidator;
use yii\base\Exception;
use yii\base\InvalidConfigException;

/**
 * SendoutElement
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 *
 * @property string $sendoutTypeLabel
 * @property float $progressFraction
 * @property MailingListElement[] $excludedMailingLists
 * @property User|null $sender
 * @property CampaignElement|null $campaign
 * @property string $progress
 * @property int $excludedMailingListCount
 * @property int $mailingListCount
 * @property MailingListElement[] $mailingLists
 * @property SegmentElement[] $segments
 * @property array $recipientIds
 * @property array $sentRecipientIds
 * @property array $pendingRecipients
 * @property bool $isCancellable
 * @property bool $isSendable
 * @property bool $canSendNow
 * @property bool $hasPendingRecipients
 * @property bool $isPausable
 * @property bool $isDeletable
 * @property bool $isResumable
 */
class SendoutElement extends Element
{
    // Constants
    // =========================================================================

    const STATUS_SENT = 'sent';
    const STATUS_SENDING = 'sending';
    const STATUS_QUEUED = 'queued';
    const STATUS_PENDING = 'pending';
    const STATUS_PAUSED = 'paused';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_FAILED = 'failed';
    const STATUS_DRAFT = 'draft';

    // Static Methods
    // =========================================================================

    /**
     * Returns the sendout types.
     *
     * @return array
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
     *
     * @return SendoutElementQuery
     */
    public static function find(): ElementQueryInterface
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
            ]
        ];

        $sources[] = ['heading' => Craft::t('campaign', 'Sendout Types')];

        $sendoutTypes = self::sendoutTypes();
        $index = 1;

        foreach ($sendoutTypes as $sendoutType => $label) {
            $sources[] = [
                'key' => 'sendoutTypeId:'.$index,
                'label' => $label,
                'data' => [
                    'handle' => $sendoutType
                ],
                'criteria' => [
                    'sendoutType' => $sendoutType
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

        // Pause
        $actions[] = PauseSendouts::class;

        // Cancel
        $actions[] = CancelSendouts::class;

        // Delete
        $actions[] = Craft::$app->getElements()->createAction([
            'type' => Delete::class,
            'confirmationMessage' => Craft::t('campaign', 'Are you sure you want to delete the selected sendouts? This action cannot be undone.'),
            'successMessage' => Craft::t('campaign', 'Sendouts deleted.'),
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
            'progress' => ['label' => Craft::t('campaign', 'Progress')],
            'sender' => ['label' => Craft::t('campaign', 'Sent By')],
            'mailingListIds' => ['label' => Craft::t('campaign', 'Mailing Lists')],
            'sendDate' => ['label' => Craft::t('campaign', 'Send Date')],
            'lastSent' => ['label' => Craft::t('campaign', 'Last Sent')],
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
        else if ($source == 'regular' OR $source == 'scheduled') {
            $attributes = ['title', 'campaignId', 'recipients', 'progress', 'sendDate', 'lastSent', 'progress'];
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

    // Properties
    // =========================================================================

    /**
     * @var string SID
     */
    public $sid;

    /**
     * @var int Campaign ID
     */
    public $campaignId;

    /**
     * @var int|null Sender ID
     */
    public $senderId;

    /**
     * @var string Sendout type
     */
    public $sendoutType;

    /**
     * @var string Send status
     */
    public $sendStatus = 'draft';

    /**
     * @var string Send status message
     */
    public $sendStatusMessage;

    /**
     * @var string Send from name
     */
    public $fromName;

    /**
     * @var string Send from email
     */
    public $fromEmail;

    /**
     * @var string Subject
     */
    public $subject;

    /**
     * @var string Notification email address
     */
    public $notificationEmailAddress;

    /**
     * @var bool Google Analytics link tracking
     */
    public $googleAnalyticsLinkTracking;

    /**
     * @var string Mailing list IDs
     */
    public $mailingListIds;

    /**
     * @var string Excluded mailing list IDs
     */
    public $excludedMailingListIds;

    /**
     * @var string Segment IDs
     */
    public $segmentIds;

    /**
     * @var int Recipients
     */
    public $recipients = 0;

    /**
     * @var int Failed recipients
     */
    public $failedRecipients = 0;

    /**
     * @var ScheduleModel|null Schedule
     */
    public $schedule;

    /**
     * @var string HTML body
     */
    public $htmlBody;

    /**
     * @var string Plaintext body
     */
    public $plaintextBody;

    /**
     * @var \DateTime Send date
     */
    public $sendDate;

    /**
     * @var \DateTime Last sent
     */
    public $lastSent;

    /**
     * @var CampaignElement|null
     */
    private $_campaign;

    /**
     * @var User|null
     */
    private $_sender;

    /**
     * @var int|null
     */
    private $_mailingListCount;

    /**
     * @var MailingListElement[]|null
     */
    private $_mailingLists;

    /**
     * @var int|null
     */
    private $_excludedMailingListCount;

    /**
     * @var MailingListElement[]|null
     */
    private $_excludedMailingLists;

    /**
     * @var SegmentElement[]|null
     */
    private $_segments;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        // Create schedule
        if ($this->sendoutType == 'automated') {
            $this->schedule = new AutomatedScheduleModel(Json::decode($this->schedule));
        }
        else if ($this->sendoutType == 'recurring') {
            $this->schedule = new RecurringScheduleModel(Json::decode($this->schedule));
        }
    }

    /**
     * @inheritdoc
     */
    public function datetimeAttributes(): array
    {
        $names = parent::datetimeAttributes();
        $names[] = 'sendDate';
        $names[] = 'lastSent';

        return $names;
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
     * @throws InvalidConfigException
     */
    public function rules(): array
    {
        $rules = parent::rules();
        $rules[] = [['recipients', 'campaignId', 'senderId'], 'integer'];
        $rules[] = [['sendoutType', 'fromName', 'fromEmail', 'subject', 'campaignId', 'mailingListIds', 'notificationEmailAddress'], 'required'];
        $rules[] = [['sid'], 'string', 'max' => 32];
        $rules[] = [['fromName', 'fromEmail', 'subject', 'notificationEmailAddress'], 'string', 'max' => 255];
        $rules[] = [['notificationEmailAddress'], 'email'];
        $rules[] = [['googleAnalyticsLinkTracking'], 'boolean'];
        $rules[] = [['sendDate'], DateTimeValidator::class];

        return $rules;
    }

    /**
     * Returns the sendout type label for the given sendout type.
     *
     * @return string
     */
    public function getSendoutTypeLabel(): string
    {
        $sendoutTypes = self::sendoutTypes();

        return $sendoutTypes[$this->sendoutType];
    }

    /**
     * Returns the sendout's progress as a fraction
     *
     * @return float
     */
    public function getProgressFraction(): float
    {
        if ($this->sendStatus == self::STATUS_SENT) {
            return 1;
        }

        // Get expected recipients
        $expectedRecipients = \count($this->getPendingRecipients());

        $progress = $expectedRecipients == 0 ?: $this->recipients / ($this->recipients + $expectedRecipients);
        $progress = $progress < 1 ? $progress : 1;

        return $progress;
    }

    /**
     * Returns the sendout's progress
     *
     * @return string
     */
    public function getProgress(): string
    {
        if ($this->sendStatus == self::STATUS_DRAFT OR $this->sendoutType == 'automated' OR $this->sendoutType == 'recurring') {
            return '';
        }

        $progress = round(100 * $this->getProgressFraction());

        return $progress.'%';
    }

    /**
     * Returns the sendout's campaign
     *
     * @return CampaignElement|null
     */
    public function getCampaign()
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
     * Returns the sender
     *
     * @return User|null
     */
    public function getSender()
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
     * Returns the sendout's mailing list count
     *
     * @return int
     */
    public function getMailingListCount(): int
    {
        if ($this->_mailingListCount !== null) {
            return $this->_mailingListCount;
        }

        $this->_mailingListCount = $this->mailingListIds ? substr_count($this->mailingListIds, ',') + 1 : 0;

        return $this->_mailingListCount;
    }

    /**
     * Returns the sendout's mailing lists
     *
     * @return MailingListElement[]
     */
    public function getMailingLists(): array
    {
        if ($this->_mailingLists !== null) {
            return $this->_mailingLists;
        }

        $this->_mailingLists = [];

        $mailingListIds = $this->mailingListIds ? explode(',', $this->mailingListIds) : [];

        foreach ($mailingListIds as $mailingListId) {
            $mailingList = Campaign::$plugin->mailingLists->getMailingListById($mailingListId);

            if ($mailingList !== null) {
                $this->_mailingLists[] = $mailingList;
            }
        }

        return $this->_mailingLists;
    }

    /**
     * Returns the sendout's excluded mailing list count
     *
     * @return int
     */
    public function getExcludedMailingListCount(): int
    {
        if ($this->_excludedMailingListCount !== null) {
            return $this->_excludedMailingListCount;
        }

        $this->_excludedMailingListCount = $this->excludedMailingListIds ? substr_count($this->excludedMailingListIds, ',') + 1 : 0;

        return $this->_excludedMailingListCount;
    }

    /**
     * Returns the sendout's excluded mailing lists
     *
     * @return MailingListElement[]
     */
    public function getExcludedMailingLists(): array
    {
        if ($this->_excludedMailingLists !== null) {
            return $this->_excludedMailingLists;
        }

        $this->_excludedMailingLists = [];

        $excludedMailingListIds = $this->excludedMailingListIds ? explode(',', $this->excludedMailingListIds) : [];

        foreach ($excludedMailingListIds as $excludedMailingListId) {
            $mailingList = Campaign::$plugin->mailingLists->getMailingListById($excludedMailingListId);

            if ($mailingList !== null) {
                $this->_excludedMailingLists[] = $mailingList;
            }
        }

        return $this->_excludedMailingLists;
    }

    /**
     * Returns the sendout's segments
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

        $this->_segments = [];

        $segmentIds = $this->segmentIds ? explode(',', $this->segmentIds) : [];

        foreach ($segmentIds as $segmentId) {
            $segment = Campaign::$plugin->segments->getSegmentById($segmentId);

            if ($segment !== null) {
                $this->_segments[] = $segment;
            }
        }

        return $this->_segments;
    }

    /**
     * Returns the sendout's pending recipients based on its mailing lists, segments and schedule
     *
     * @return array
     */
    public function getPendingRecipients(): array
    {
        $recipients = [];

        $mailingLists = $this->getMailingLists();
        $excludedMailingLists = $this->getExcludedMailingLists();
        $segments = $this->getSegments();

        // Add mailing list subscribers
        foreach ($mailingLists as $mailingList) {
            /** @var MailingListElement $mailingList */
            $contacts = $mailingList->getSubscribedContacts();
            foreach ($contacts as $contact) {
                // If contact has not yet been added
                /** @var ContactElement $contact */
                if (empty($recipients[$contact->id])) {
                    $recipients[$contact->id] = ['contactId' => $contact->id, 'mailingListId' => $mailingList->id];
                }
            }
        }

        // Remove excluded mailing list subscribers
        foreach ($excludedMailingLists as $mailingList) {
            /** @var MailingListElement $mailingList */
            $contacts = $mailingList->getSubscribedContacts();
            foreach ($contacts as $contact) {
                // If contact has been added then unset
                if (isset($recipients[$contact->id])) {
                    unset($recipients[$contact->id]);
                }
            }
        }

        // Check whether we should remove recipients that were sent to today only
        $todayOnly = ($this->sendoutType == 'recurring' AND $this->schedule->canSendToContactsMultipleTimes === true);

        // Get sent recipient IDs
        $sentRecipientIds = $this->getSentRecipientIds($todayOnly);

        // Remove contacts that are already sent recipients
        $recipients = array_diff_key($recipients, array_flip($sentRecipientIds));

        foreach ($segments as $segment) {
            // Keep only contacts that exist in the segment
            $recipients = array_intersect_key($recipients, array_flip($segment->getContactIds()));
        }

        if ($this->sendoutType == 'automated') {
            /** @var AutomatedScheduleModel $automatedSchedule */
            $automatedSchedule = $this->schedule;

            // Remove any contacts that do not meet the conditions
            foreach ($recipients as $key => $recipientData) {
                /** @var ContactMailingListRecord $contactMailingListRecord */
                $contactMailingListRecord = ContactMailingListRecord::findOne($recipientData);

                if ($contactMailingListRecord === null) {
                    unset($recipients[$key]);
                    continue;
                }

                $subscribedDateTime = DateTimeHelper::toDateTime($contactMailingListRecord->subscribed);
                $subscribedDateTimePlusDelay = $subscribedDateTime->modify('+'.$automatedSchedule->timeDelay.' '.$automatedSchedule->timeDelayInterval);

                // If subscribed date was before sendout was created or time plus delay has not yet passed
                if ($subscribedDateTime < $this->dateCreated OR !DateTimeHelper::isInThePast($subscribedDateTimePlusDelay)) {
                    unset($recipients[$key]);
                }
            }
        }

        return $recipients;
    }

    /**
     * Returns the sendout's sent recipient ID's
     *
     * @param bool $todayOnly
     * @return array
     */
    public function getSentRecipientIds($todayOnly = false): array
    {
        $query = ContactCampaignRecord::find()
            ->select('contactId')
            ->where(['sendoutId' => $this->id]);

        if ($todayOnly) {
            $now = new \DateTime();

            // Add condition that sent is today
            $query->andWhere(Db::parseDateParam('sent', $now->format('Y-m-d'), '>'));
        }

        $contactCampaignRecords = $query->all();

        $sentRecipientIds = [];

        /** @var ContactCampaignRecord $contactCampaignRecord */
        foreach ($contactCampaignRecords as $contactCampaignRecord) {
            $sentRecipientIds[] = $contactCampaignRecord->contactId;
        }

        return $sentRecipientIds;
    }

    /**
     * Returns the sendout's HTML body
     *
     * @return string|null
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function getHtmlBody()
    {
        if ($this->sendStatus == 'sent') {
            return $this->htmlBody;
        }

        $campaign = $this->getCampaign();

        if ($campaign === null) {
            return '';
        }

        return $campaign->getHtmlBody();
    }

    /**
     * Returns the sendout's plaintext body
     *
     * @return string|null
     * @throws InvalidConfigException
     * @throws Exception
     */
    public function getPlaintextBody()
    {
        if ($this->sendStatus == 'sent') {
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
    public function getStatus()
    {
        return $this->sendStatus;
    }

    /**
     * @inheritdoc
     */
    public function getIsEditable(): bool
    {
        return ($this->getStatus() == self::STATUS_DRAFT OR $this->getStatus() == self::STATUS_PAUSED);
    }

    /**
     * Returns whether the sendout has pending recipients
     *
     * @return bool
     */
    public function getHasPendingRecipients(): bool
    {
        return \count($this->getPendingRecipients()) > 0;
    }

    /**
     * Returns whether the sendout can be sent now
     */
    public function getCanSendNow(): bool
    {
        if ($this->sendoutType == 'automated' OR $this->sendoutType == 'recurring') {
            return $this->schedule->canSendNow($this);
        }

        return true;
    }

    /**
     * Returns whether the sendout is sendable
     *
     * @return bool
     */
    public function getIsSendable(): bool
    {
        return ($this->getStatus() == self::STATUS_SENDING OR $this->getStatus() == self::STATUS_QUEUED);
    }

    /**
     * Returns whether the sendout is pausable
     *
     * @return bool
     */
    public function getIsPausable(): bool
    {
        return ($this->getStatus() == self::STATUS_SENDING OR $this->getStatus() == self::STATUS_QUEUED OR $this->getStatus() == self::STATUS_PENDING OR $this->getStatus() == self::STATUS_FAILED);
    }

    /**
     * Returns whether the sendout is resumable
     *
     * @return bool
     */
    public function getIsResumable(): bool
    {
        return ($this->getStatus() == self::STATUS_PAUSED OR $this->getStatus() == self::STATUS_FAILED);
    }

    /**
     * Returns whether the sendout is cancellable
     *
     * @return bool
     */
    public function getIsCancellable(): bool
    {
        return ($this->getStatus() != self::STATUS_DRAFT AND $this->getStatus() != self::STATUS_CANCELLED AND $this->getStatus() != self::STATUS_SENT);
    }

    /**
     * Returns whether the sendout is deletable
     *
     * @return bool
     */
    public function getIsDeletable(): bool
    {
        return (!$this->getIsPausable() OR $this->getStatus() == self::STATUS_FAILED);
    }

    /**
     * @inheritdoc
     */
    public function getCpEditUrl()
    {
        return UrlHelper::cpUrl('campaign/sendouts/'.$this->sendoutType.'/'.$this->id);
    }

    // Indexes, etc.
    // -------------------------------------------------------------------------

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
    public function getEditorHtml(): string
    {
        // Get the title field
        $html = Craft::$app->getView()->renderTemplate('campaign/sendouts/_includes/titlefield', [
            'sendout' => $this
        ]);

        $html .= parent::getEditorHtml();

        return $html;
    }

    /**
     * @inheritdoc
     * @param string $attribute
     *
     * @return string
     * @throws Exception
     * @throws \Twig_Error_Loader
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
                return $this->getMailingListCount();
        }

        return parent::tableAttributeHtml($attribute);
    }

    // Events
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public function beforeSave(bool $isNew): bool
    {
        if ($isNew) {
            // Create unique ID
            $this->sid = StringHelper::uniqueId('s');
        }

        return parent::beforeSave($isNew);
    }

    /**
     * @inheritdoc
     */
    public function afterSave(bool $isNew)
    {
        if ($isNew) {
            $sendoutRecord = new SendoutRecord();
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
