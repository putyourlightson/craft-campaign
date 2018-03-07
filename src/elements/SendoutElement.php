<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\elements;

use craft\elements\User;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\db\SendoutElementQuery;
use putyourlightson\campaign\elements\actions\PauseSendouts;
use putyourlightson\campaign\elements\actions\ResumeSendouts;
use putyourlightson\campaign\elements\actions\CancelSendouts;
use putyourlightson\campaign\helpers\StringHelper;
use putyourlightson\campaign\records\SendoutRecord;

use Craft;
use craft\base\Element;
use craft\elements\db\ElementQueryInterface;
use craft\elements\actions\Edit;
use craft\elements\actions\Delete;
use craft\helpers\Template;
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
 * @property string               $sendoutTypeLabel
 * @property float                $progressFraction
 * @property MailingListElement[] $excludedMailingLists
 * @property User|null            $sender
 * @property CampaignElement|null $campaign
 * @property string               $progress
 * @property int                  $excludedMailingListCount
 * @property int                  $mailingListCount
 * @property SegmentElement[]     $segments
 * @property array                $recipientIds
 * @property MailingListElement[] $mailingLists
 */
class SendoutElement extends Element
{
    // Constants
    // =========================================================================

    const STATUS_SENT = 'active';
    const STATUS_SENDING = 'active running';
    const STATUS_QUEUED = 'pending running';
    const STATUS_PENDING = 'pending';
    const STATUS_PAUSED = 'suspended running';
    const STATUS_CANCELLED = 'suspended';
    const STATUS_FAILED = 'failed';
    const STATUS_DRAFT = 'draft';

    // Static Methods
    // =========================================================================

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
        $elementQuery->sendoutType = array_keys(self::getSendoutTypes());

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

        $sendoutTypes = self::getSendoutTypes();
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

        // Edit
        $actions[] = Craft::$app->getElements()->createAction([
            'type' => Edit::class,
        ]);

        // Pause
        $actions[] = PauseSendouts::class;

        // Resume
        $actions[] = ResumeSendouts::class;

        // Cancel
        $actions[] = CancelSendouts::class;

        // Delete
        $actions[] = Craft::$app->getElements()->createAction([
            'type' => Delete::class,
            'confirmationMessage' => Craft::t('campaign', 'Are you sure you want to delete the selected sendouts?'),
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
            'sender' => ['label' => Craft::t('campaign', 'Last Sent By')],
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
     * Returns the sendout types.
     *
     * @return array
     */
    public static function getSendoutTypes(): array
    {
        $sendoutTypes = [
            'regular' => Craft::t('campaign', 'Regular'),
            'scheduled' => Craft::t('campaign', 'Scheduled'),
            'automated' => Craft::t('campaign', 'Automated'),
        ];

        // Campaign lite
        if (Campaign::$plugin->isLite()) {
            unset($sendoutTypes['automated']);
        }

        return $sendoutTypes;
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
     * @var int|null User ID
     */
    public $userId;

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
     * @var string Pending recipient IDs
     */
    public $pendingRecipientIds;

    /**
     * @var string Sent recipient IDs
     */
    public $sentRecipientIds;

    /**
     * @var string Failed recipient IDs
     */
    public $failedRecipientIds;

    /**
     * @var mixed Automated schedule
     */
    public $automatedSchedule;

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

        // Decode the automated schedule
        $this->automatedSchedule = Json::decode($this->automatedSchedule);
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
    public function attributeLabels()
    {
        $labels = parent::attributeLabels();

        // Set the field labels
        $labels['campaignId'] = Craft::t('campaign', 'Campaign');
        $labels['mailingListIds'] = Craft::t('campaign', 'Mailing Lists');

        return $labels;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();
        $rules[] = [['recipients', 'campaignId', 'userId'], 'number', 'integerOnly' => true];
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
        $sendoutTypes = self::getSendoutTypes();

        return $sendoutTypes[$this->sendoutType];
    }

    /**
     * Returns the sendout's progress as a fraction
     *
     * @return float
     */
    public function getProgressFraction(): float
    {
        if ($this->status == self::STATUS_SENT) {
            return 1;
        }

        // Get number of recipients
        $pendingRecipients = $this->pendingRecipientIds ? substr_count($this->pendingRecipientIds, ',') + 1 : 0;

        $totalRecipients = $this->recipients + $pendingRecipients;

        return $totalRecipients == 0 ?: $this->recipients / $totalRecipients;
    }

    /**
     * Returns the sendout's progress
     *
     * @return string
     */
    public function getProgress(): string
    {
        if ($this->status == self::STATUS_DRAFT OR $this->sendoutType == 'automated') {
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
        if ($this->userId === null) {
            return null;
        }

        if ($this->_sender !== null) {
            return $this->_sender;
        }

        $this->_sender = Craft::$app->getUsers()->getUserById($this->userId);

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
            $this->_mailingLists[] = Campaign::$plugin->mailingLists->getMailingListById($mailingListId);
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
            $this->_excludedMailingLists[] = Campaign::$plugin->mailingLists->getMailingListById($excludedMailingListId);
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
        if ($this->_segments !== null) {
            return $this->_segments;
        }

        $this->_segments = [];

        $segmentIds = $this->segmentIds ? explode(',', $this->segmentIds) : [];

        foreach ($segmentIds as $segmentId) {
            $this->_segments[] = Campaign::$plugin->segments->getSegmentById($segmentId);
        }

        return $this->_segments;
    }

    /**
     * Returns the sendout's recipients based on its mailing lists and segments
     *
     * @return array
     */
    public function getRecipientIds(): array
    {
        $recipientIds = [];
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
                if (!\in_array($contact->id, $recipientIds, true)) {
                    $recipientIds[] = $contact->id;
                }
            }
        }

        // Remove excluded mailing list subscribers
        foreach ($excludedMailingLists as $mailingList) {
            /** @var MailingListElement $mailingList */
            $contacts = $mailingList->getSubscribedContacts();

            foreach ($contacts as $contact) {
                // If contact has been added then unset
                /** @var ContactElement $contact */
                $key = array_search($contact->id, $recipientIds, true);
                if ($key !== false) {
                    unset($recipientIds[$key]);
                }
            }
        }

        // Remove all contacts that do not exist in segments
        foreach ($segments as $segment) {
            // Keep only contacts that exist in the segment
            $recipientIds = array_intersect($recipientIds, $segment->getContactIds());
        }

        return $recipientIds;
    }

    /**
     * Returns the sendout's HTML body
     *
     * @return string|null
     * @throws \Twig_Error_Loader
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function getHtmlBody()
    {
        if ($this->sendStatus == 'sent') {
            return $this->htmlBody;
        }

        return $this->getCampaign()->getHtmlBody();
    }

    /**
     * Returns the sendout's plaintext body
     *
     * @return string|null
     * @throws InvalidConfigException
     * @throws \Twig_Error_Loader
     * @throws Exception
     */
    public function getPlaintextBody()
    {
        if ($this->sendStatus == 'sent') {
            return $this->plaintextBody;
        }

        return $this->getCampaign()->getPlaintextBody();
    }

    /**
     * @inheritdoc
     */
    public function getStatus()
    {
        switch ($this->sendStatus) {
            case 'sent':
                return self::STATUS_SENT;
            case 'sending':
                return self::STATUS_SENDING;
            case 'queued':
                return self::STATUS_QUEUED;
            case 'pending':
                return self::STATUS_PENDING;
            case 'paused':
                return self::STATUS_PAUSED;
            case 'cancelled':
                return self::STATUS_CANCELLED;
            case 'failed':
                return self::STATUS_FAILED;
            default:
                return self::STATUS_DRAFT;
        }
    }

    /**
     * @inheritdoc
     */
    public function getIsEditable(): bool
    {
        return ($this->getStatus() == self::STATUS_DRAFT OR $this->getStatus() == self::STATUS_PAUSED);
    }

    /**
     * Returns whether the sendout is sendable
     *
     * @return bool
     */
    public function isSendable(): bool
    {
        return ($this->getStatus() == self::STATUS_SENDING OR $this->getStatus() == self::STATUS_QUEUED);
    }

    /**
     * Returns whether the sendout is pausable
     *
     * @return bool
     */
    public function isPausable(): bool
    {
        return ($this->getStatus() == self::STATUS_SENDING OR $this->getStatus() == self::STATUS_QUEUED OR $this->getStatus() == self::STATUS_PENDING OR $this->getStatus() == self::STATUS_FAILED);
    }

    /**
     * Returns whether the sendout is resumable
     *
     * @return bool
     */
    public function isResumable(): bool
    {
        return ($this->getStatus() == self::STATUS_PAUSED OR $this->getStatus() == self::STATUS_FAILED);
    }

    /**
     * Returns whether the sendout is cancellable
     *
     * @return bool
     */
    public function isCancellable(): bool
    {
        return ($this->getStatus() != self::STATUS_DRAFT AND $this->getStatus() != self::STATUS_CANCELLED AND $this->getStatus() != self::STATUS_SENT);
    }

    /**
     * Returns whether the sendout is deletable
     *
     * @return bool
     */
    public function isDeletable(): bool
    {
        return (!$this->isPausable() OR $this->getStatus() == self::STATUS_FAILED);
    }

    /**
     * @inheritdoc
     */
    public function getCpEditUrl()
    {
        return UrlHelper::cpUrl('campaign/sendouts/'.$this->sendoutType.'/'.$this->id);
    }

    /**
     * @inheritdoc
     */
    protected static function defineSearchableAttributes(): array
    {
        return ['title', 'sid', 'subject', 'fromName'];
    }

    // Indexes, etc.
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public function getHtmlAttributes(string $context): array
    {
        $htmlAttributes = parent::getHtmlAttributes($context);

        if ($this->isPausable()) {
            $htmlAttributes['data-pausable'] = null;
        }

        if ($this->isResumable()) {
            $htmlAttributes['data-resumable'] = null;
        }

        if ($this->isCancellable()) {
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
                return Template::raw('<a href="'.$campaign->getCpEditUrl().'">'.$campaign->title.'</a>');

            case 'sender':
                $sender = $this->getSender();
                return $sender ? Craft::$app->getView()->renderTemplate('_elements/element', ['element' => $sender]) : '';

            case 'mailingListIds':
                return $this->getMailingListCount();
        }

        return parent::tableAttributeHtml($attribute);
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
