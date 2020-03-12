<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\elements;

use craft\elements\actions\Restore;
use DateTime;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\actions\DeleteContacts;
use putyourlightson\campaign\elements\db\ContactElementQuery;
use putyourlightson\campaign\helpers\StringHelper;
use putyourlightson\campaign\records\ContactMailingListRecord;
use putyourlightson\campaign\records\ContactRecord;

use Craft;
use craft\base\Element;
use craft\behaviors\FieldLayoutBehavior;
use craft\elements\db\ElementQueryInterface;
use craft\elements\actions\Edit;
use craft\elements\actions\Delete;
use craft\helpers\Json;
use craft\helpers\Template;
use craft\helpers\UrlHelper;
use craft\validators\DateTimeValidator;
use craft\validators\UniqueValidator;
use yii\base\Exception;
use yii\base\InvalidConfigException;

/**
 * ContactElement
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 *
 * @property int $unsubscribedCount
 * @property int $complainedCount
 * @property MailingListElement[] $complainedMailingLists
 * @property int $bouncedCount
 * @property string $reportUrl
 * @property MailingListElement[] $unsubscribedMailingLists
 * @property MailingListElement[] $subscribedMailingLists
 * @property MailingListElement[] $bouncedMailingLists
 * @property int $pendingCount
 * @property string $countryCode
 * @property MailingListElement[] $mailingLists
 * @property bool $isSubscribed
 * @property int $subscribedCount
 */
class ContactElement extends Element
{
    // Constants
    // =========================================================================

    const STATUS_ACTIVE = 'active';
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
            self::STATUS_COMPLAINED => Craft::t('campaign', 'Complained'),
            self::STATUS_BOUNCED => Craft::t('campaign', 'Bounced')
        ];
    }

    /**
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

        $mailingLists = Campaign::$plugin->mailingLists->getAllMailingLists();

        /** @var MailingListElement $mailingList */
        foreach ($mailingLists as $mailingList) {
            $sources[] = [
                'key' => 'mailingList:'.$mailingList->id,
                'label' => $mailingList->title,
                'sites' => [$mailingList->siteId],
                'data' => [
                    'id' => $mailingList->id
                ],
                'criteria' => [
                    'mailingListId' => $mailingList->id
                ],
                'hasThumbs' => true
            ];
        }

        if (Campaign::$plugin->getIsPro()) {
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
        $actions[] = DeleteContacts::class;

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
                'attribute' => 'dateCreated'
            ],
            [
                'label' => Craft::t('app', 'Date Updated'),
                'orderBy' => 'elements.dateUpdated',
                'attribute' => 'dateUpdated'
            ],
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
            'verified' => ['label' => Craft::t('campaign', 'Verified')],
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

        array_push($attributes, 'country', 'lastActivity');

        return $attributes;
    }

    // Public Properties
    // =========================================================================

    /**
     * User ID
     *
     * @var int|null
     */
    public $userId;

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
     * Country name
     *
     * @var string|null
     */
    public $country;

    /**
     * GeoIP
     *
     * @var mixed
     */
    public $geoIp;

    /**
     * Device
     *
     * @var string|null
     */
    public $device;

    /**
     * OS
     *
     * @var string|null
     */
    public $os;

    /**
     * Client
     *
     * @var string|null
     */
    public $client;

    /**
     * Last activity
     *
     * @var DateTime
     */
    public $lastActivity;

    /**
     * Verified
     *
     * @var DateTime
     */
    public $verified;

    /**
     * Complained
     *
     * @var DateTime
     */
    public $complained;

    /**
     * Bounced
     *
     * @var DateTime
     */
    public $bounced;

    /**
     * Subscription status, only used when a mailing list is selected in element index
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
    public function init()
    {
        parent::init();

        // Decode geoIp if not empty
        $this->geoIp = !empty($this->geoIp) ? Json::decode($this->geoIp) : null;
    }

    /**
     * @inheritdoc
     */
    public function datetimeAttributes(): array
    {
        $names = parent::datetimeAttributes();
        $names[] = 'lastActivity';
        $names[] = 'verified';
        $names[] = 'complained';
        $names[] = 'bounced';

        return $names;
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
     * @throws InvalidConfigException
     */
    public function rules(): array
    {
        $rules = parent::rules();

        $rules[] = [['cid', 'email'], 'required'];
        $rules[] = [['cid'], 'string', 'max' => 32];
        $rules[] = [['email'], 'email'];
        $rules[] = [['email'], UniqueValidator::class, 'targetClass' => ContactRecord::class, 'caseInsensitive' => true];
        $rules[] = [['lastActivity', 'verified', 'complained', 'bounced'], DateTimeValidator::class];

        return $rules;
    }

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function getFieldLayout()
    {
        /** @var FieldLayoutBehavior $fieldLayoutBehavior */
        $fieldLayoutBehavior = Campaign::$plugin->getSettings()->getBehavior('contactFieldLayout');

        return $fieldLayoutBehavior->getFieldLayout();
    }

    /**
     * @inheritdoc
     */
    public function getSupportedSites(): array
    {
        return Craft::$app->getSites()->getAllSiteIds();
    }

    /**
     * Checks whether the contact is subscribed to at least one mailing list
     *
     * @return bool
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
     * Returns the unsubscribe URL
     *
     * @param SendoutElement|null $sendout
     *
     * @return string
     * @throws Exception
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

        $path = Craft::$app->getConfig()->getGeneral()->actionTrigger.'/campaign/t/unsubscribe';

        return UrlHelper::siteUrl($path, $params);
    }

    /**
     * Returns the mailing list subscription status
     *
     * @param int $mailingListId
     *
     * @return string
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
     * Returns the number of subscribed mailing lists
     *
     * @return int
     */
    public function getSubscribedCount(): int
    {
        return $this->_getMailingListCount('subscribed');
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
     * Returns all mailing lists that this contact is in
     *
     * @return MailingListElement[]
     */
    public function getMailingLists(): array
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
        return $this->geoIp['countryCode'] ?? '';
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

        return self::STATUS_ACTIVE;
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

            $contactRecord->save(false);
        }

        parent::afterSave($isNew);
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns the number of mailing lists that this contact is in
     *
     * @param string|null $subscriptionStatus
     *
     * @return int
     */
    private function _getMailingListCount(string $subscriptionStatus = null): int
    {
        $subscriptionStatus = $subscriptionStatus ?? '';

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
     * @param string|null $subscriptionStatus
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
