<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\elements;

use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\db\ContactElementQuery;
use putyourlightson\campaign\helpers\GeoIpHelper;
use putyourlightson\campaign\helpers\StringHelper;
use putyourlightson\campaign\records\ContactMailingListRecord;
use putyourlightson\campaign\records\ContactRecord;

use Craft;
use craft\base\Element;
use craft\behaviors\FieldLayoutBehavior;
use craft\elements\db\ElementQueryInterface;
use craft\elements\actions\Edit;
use craft\elements\actions\Delete;
use craft\helpers\Template;
use craft\helpers\UrlHelper;
use craft\validators\DateTimeValidator;
use craft\validators\UniqueValidator;
use yii\base\Exception;

/**
 * ContactElement
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 *
 * @property int                  $unsubscribedCount
 * @property int                  $complainedCount
 * @property MailingListElement[] $complainedMailingLists
 * @property int                  $bouncedCount
 * @property string               $reportUrl
 * @property MailingListElement[] $unsubscribedMailingLists
 * @property MailingListElement[] $subscribedMailingLists
 * @property MailingListElement[] $bouncedMailingLists
 * @property int                  $pendingCount
 * @property string               $countryCode
 * @property MailingListElement[] $allMailingLists
 * @property int                  $subscribedCount
 * @property array                $location
 */
class ContactElement extends Element
{
    // Constants
    // =========================================================================

    const STATUS_ACTIVE = 'active';
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLAINED = 'complained';
    const STATUS_BOUNCED = 'bounced';

    // Static Methods
    // =========================================================================

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
            self::STATUS_PENDING => Craft::t('campaign', 'Pending'),
            self::STATUS_COMPLAINED => Craft::t('campaign', 'Complained'),
            self::STATUS_BOUNCED => Craft::t('campaign', 'Bounced')
        ];
    }

    /**
     * @inheritdoc
     *
     * @return ContactElementQuery
     */
    public static function find(): ElementQueryInterface
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
                'hasThumbs' => true
            ]
        ];

        $sources[] = ['heading' => Craft::t('campaign', 'Mailing Lists')];

        $mailingLists = MailingListElement::findAll();

        foreach ($mailingLists as $mailingList) {
            $sources[] = [
                'key' => 'mailingList:'.$mailingList->id,
                'label' => $mailingList->title,
                'data' => [
                    'id' => $mailingList->id
                ],
                'criteria' => [
                    'mailingListId' => $mailingList->id
                ],
                'hasThumbs' => true
            ];
        }

        $sources[] = ['heading' => Craft::t('campaign', 'Segments')];

        $segments = SegmentElement::findAll();

        foreach ($segments as $segment) {
            $sources[] = [
                'key' => 'segment:'.$segment->id,
                'label' => $segment->title,
                'data' => [
                    'id' => $segment->id
                ],
                'criteria' => [
                    'segmentId' => $segment->id
                ],
                'hasThumbs' => true
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

        // Edit
        $actions[] = Craft::$app->getElements()->createAction([
            'type' => Edit::class,
            'label' => Craft::t('campaign', 'Edit contact'),
        ]);

        // Delete
        $actions[] = Craft::$app->getElements()->createAction([
            'type' => Delete::class,
            'confirmationMessage' => Craft::t('campaign', 'Are you sure you want to delete the selected contacts?'),
            'successMessage' => Craft::t('campaign', 'Contacts deleted.'),
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
            'elements.dateCreated' => Craft::t('app', 'Date Created'),
            'elements.dateUpdated' => Craft::t('app', 'Date Updated'),
        ];
    }

    /**
     * @inheritdoc
     */
    protected static function defineTableAttributes(): array
    {
        $settings = Campaign::$plugin->getSettings();

        $attributes = [
            'email' => ['label' => $settings->emailFieldLabel],
            'subscriptionStatus' => ['label' => Craft::t('campaign', 'Subscription Status')],
            'country' => ['label' => Craft::t('campaign', 'Country')],
            'lastActivity' => ['label' => Craft::t('campaign', 'Last Activity')],
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
        $attributes = ['email'];

        if ($source !== '*') {
            $attributes[] = 'subscriptionStatus';
        }

        $attributes = array_merge($attributes, ['country', 'lastActivity']);

        return $attributes;
    }

    // Public Properties
    // =========================================================================

    /**
     * Contact ID
     *
     * @var string
     */
    public $cid;

    /**
     * Email
     *
     * @var string
     */
    public $email;

    /**
     * Pending
     *
     * @var bool
     */
    public $pending = true;

    /**
     * Country name
     *
     * @var string|null
     */
    public $country;

    /**
     * GeoIP
     *
     * @var string|null
     */
    public $geoIp;

    /**
     * @var string|null Device
     */
    public $device;

    /**
     * @var string|null OS
     */
    public $os;

    /**
     * @var string|null Client
     */
    public $client;

    /**
     * Last activity
     *
     * @var \DateTime
     */
    public $lastActivity;

    /**
     * Complained
     *
     * @var \DateTime
     */
    public $complained;

    /**
     * Bounced
     *
     * @var \DateTime
     */
    public $bounced;

    /**
     * Subscription status
     *
     * @var string
     */
    public $subscriptionStatus;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function __toString()
    {
        return $this->email;
    }

    /**
     * @inheritdoc
     */
    public function datetimeAttributes(): array
    {
        $names = parent::datetimeAttributes();
        $names[] = 'lastActivity';
        $names[] = 'complained';
        $names[] = 'bounced';

        return $names;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        $labels = parent::attributeLabels();

        // Set the email field label
        $labels['email'] = Campaign::$plugin->getSettings()->emailFieldLabel;

        return $labels;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();
        $rules[] = [['cid', 'email'], 'required'];
        $rules[] = [['cid'], 'string', 'max' => 32];
        $rules[] = [['email'], 'email'];
        $rules[] = [['email'], UniqueValidator::class, 'targetClass' => ContactRecord::class];
        $rules[] = [['lastActivity', 'complained', 'bounced'], DateTimeValidator::class];

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getFieldLayout()
    {
        /** @var FieldLayoutBehavior $fieldLayoutBehavior */
        $fieldLayoutBehavior = Campaign::$plugin->getSettings()->getBehavior('contactFieldLayout');

        return $fieldLayoutBehavior->getFieldLayout();
    }

    /**
     * Checks whether the contact is subscribed to at least one mailing list
     *
     * @return bool
     */
    public function isSubscribed(): bool
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
     * Returns the unsubscribe URL
     *
     * @param SendoutElement|null $sendout
     *
     * @return string
     * @throws Exception
     */
    public function getUnsubscribeUrl($sendout = null): string
    {
        if ($this->cid === null) {
            return '';
        }

        $params = ['cid' => $this->cid];

        if ($sendout !== null) {
            $params['sid'] = $sendout->sid;
        }

        $path = Craft::$app->getConfig()->getGeneral()->actionTrigger.'/campaign/t/unsubscribe';

        return UrlHelper::siteUrl($path, $params);
    }

    /**
     * Returns the mailing list subscription status
     *
     * @param int Mailing list ID
     *
     * @return string
     */
    public function getMailingListSubscriptionStatus(int $mailingListId): string
    {
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
     * Returns a subscribed mailing list in a sendout
     *
     * @param SendoutElement $sendout
     *
     * @return MailingListElement|null
     */
    public function getSubscribedMailingListInSendout(SendoutElement $sendout)
    {
        $subscribedMailingList = null;

        $subscribedMailingLists = $this->getSubscribedMailingLists();
        $sendoutMailingListIds = explode(',', $sendout->mailingListIds);

        foreach ($subscribedMailingLists as $mailingList) {
            if (\in_array($mailingList->id, $sendoutMailingListIds, false)) {
                $subscribedMailingList = $mailingList;
                break;
            }
        }

        return $subscribedMailingList;
    }

    /**
     * Returns the number of subscribed mailing lists
     *
     * @return int
     */
    public function getSubscribedCount(): int
    {
        return $this->_getMailingListCount('subscribed');
    }

    /**
     * Returns the number of pending mailing lists
     *
     * @return int
     */
    public function getPendingCount(): int
    {
        return $this->_getMailingListCount('pending');
    }

    /**
     * Returns the number of unsubscribed mailing lists
     *
     * @return int
     */
    public function getUnsubscribedCount(): int
    {
        return $this->_getMailingListCount('unsubscribed');
    }

    /**
     * Returns the number of complained mailing lists
     *
     * @return int
     */
    public function getComplainedCount(): int
    {
        return $this->_getMailingListCount('complained');
    }

    /**
     * Returns the number of bounced mailing lists
     *
     * @return int
     */
    public function getBouncedCount(): int
    {
        return $this->_getMailingListCount('bounced');
    }

    /**
     * Returns the subscribed mailing lists
     *
     * @return MailingListElement[]
     */
    public function getSubscribedMailingLists(): array
    {
        return $this->_getMailingLists('subscribed');
    }

    /**
     * Returns the subscribed mailing lists
     *
     * @return MailingListElement[]
     */
    public function getUnsubscribedMailingLists(): array
    {
        return $this->_getMailingLists('unsubscribed');
    }

    /**
     * Returns the complained mailing lists
     *
     * @return MailingListElement[]
     */
    public function getComplainedMailingLists(): array
    {
        return $this->_getMailingLists('complained');
    }

    /**
     * Returns the bounced mailing lists
     *
     * @return MailingListElement[]
     */
    public function getBouncedMailingLists(): array
    {
        return $this->_getMailingLists('bounced');
    }

    /**
     * Returns all mailing lists
     *
     * @return MailingListElement[]
     */
    public function getAllMailingLists(): array
    {
        return $this->_getMailingLists();
    }

    /**
     * Returns the country code
     *
     * @return string
     */
    public function getCountryCode(): string
    {
        return GeoIpHelper::getCountryCode($this->geoIp);
    }

    /**
     * Returns the location
     *
     * @return array
     */
    public function getLocation(): array
    {
        return GeoIpHelper::getLocation($this->geoIp);
    }

    /**
     * @inheritdoc
     */
    public function getStatus()
    {
        if ($this->bounced) {
            return self::STATUS_BOUNCED;
        }

        if ($this->complained) {
            return self::STATUS_COMPLAINED;
        }

        if (!$this->pending) {
            return self::STATUS_ACTIVE;
        }

        return self::STATUS_PENDING;
    }

    /**
     * @inheritdoc
     */
    public function getThumbUrl(int $size)
    {
        // Get image from Gravatar, default to blank
        $photo = 'https://www.gravatar.com/avatar/'.md5(strtolower(trim($this->email))).'?d=blank&s='.$size;

        return $photo;
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
        return UrlHelper::cpUrl('campaign/contacts/'.$this->id);
    }

    /**
     * Returns the contact's report URL
     *
     * @return string
     */
    public function getReportUrl(): string
    {
        return UrlHelper::cpUrl('campaign/reports/contacts/'.$this->id);
    }

    /**
     * @inheritdoc
     */
    protected static function defineSearchableAttributes(): array
    {
        return ['email', 'cid'];
    }

    // Indexes, etc.
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    protected function tableAttributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'subscriptionStatus':
                if ($this->subscriptionStatus) {
                    return Template::raw('<label class="subscriptionStatus '.$this->subscriptionStatus.'">'.$this->subscriptionStatus.'</label>');
                }
        }

        return parent::tableAttributeHtml($attribute);
    }

    /**
     * @inheritdoc
     */
    public function getEditorHtml(): string
    {
        $html = Craft::$app->getView()->renderTemplate('campaign/contacts/_includes/titlefield', [
            'contact' => $this,
            'settings' => Campaign::$plugin->getSettings(),
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
            $this->cid = StringHelper::uniqueId('c');
        }

        return parent::beforeSave($isNew);
    }

    /**
     * @inheritdoc
     */
    public function afterSave(bool $isNew)
    {
        if ($isNew) {
            $contactRecord = new ContactRecord();
            $contactRecord->id = $this->id;
            $contactRecord->cid = $this->cid;
        }
        else {
            $contactRecord = ContactRecord::findOne($this->id);
        }

        if ($contactRecord) {
            // Set attributes
            $contactRecord->email = $this->email;
            $contactRecord->pending = $this->pending;
            $contactRecord->country = $this->country;
            $contactRecord->geoIp = $this->geoIp;
            $contactRecord->device = $this->device;
            $contactRecord->os = $this->os;
            $contactRecord->client = $this->client;
            $contactRecord->lastActivity = $this->lastActivity;
            $contactRecord->complained = $this->complained;
            $contactRecord->bounced = $this->bounced;

            $contactRecord->save(false);
        }

        parent::afterSave($isNew);
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns the number of mailing lists that this contact is in
     *
     * @param string|null
     *
     * @return int
     */
    private function _getMailingListCount(string $subscriptionStatus = ''): int
    {
        $condition = ['contactId' => $this->id];

        if ($subscriptionStatus) {
            $condition['subscriptionStatus'] = $subscriptionStatus;
        }

        $count = ContactMailingListRecord::find()
            ->where($condition)
            ->count();

        return $count;
    }

    /**
     * Returns the mailing lists that this contact is in
     *
     * @param string|null
     *
     * @return MailingListElement[]
     */
    private function _getMailingLists(string $subscriptionStatus = ''): array
    {
        $mailingLists = [];

        $condition = ['contactId' => $this->id];

        if ($subscriptionStatus) {
            $condition['subscriptionStatus'] = $subscriptionStatus;
        }

        $contactMailingLists = ContactMailingListRecord::find()
            ->select('mailingListId')
            ->where($condition)
            ->all();

        foreach ($contactMailingLists as $contactMailingList) {
            /* @var ContactMailingListRecord $contactMailingList */
            $mailingList = Campaign::$plugin->mailingLists->getMailingListById($contactMailingList->mailingListId);

            if ($mailingList) {
                $mailingLists[] = $mailingList;
            }
        }

        return $mailingLists;
    }
}
