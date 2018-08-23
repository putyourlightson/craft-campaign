<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\models;

use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\base\BaseModel;
use putyourlightson\campaign\elements\CampaignElement;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\elements\SendoutElement;
use putyourlightson\campaign\records\LinkRecord;

/**
 * ContactCampaignModel
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 *
 * @property string $countryCode
 * @property ContactElement $contact
 * @property string $interaction
 * @property CampaignElement $campaign
 * @property string $interactions
 * @property MailingListElement|null $mailingList
 * @property SendoutElement|null $sendout
 * @property array $location
 */
class ContactCampaignModel extends BaseModel
{
    // Constants
    // =========================================================================

    const INTERACTIONS = ['opened', 'clicked', 'unsubscribed', 'complained', 'bounced'];

    // Properties
    // =========================================================================

    /**
     * @var int|null ID
     */
    public $id;

    /**
     * @var int Contact ID
     */
    public $contactId;

    /**
     * @var int Campaign ID
     */
    public $campaignId;

    /**
     * @var int Sendout ID
     */
    public $sendoutId;

    /**
     * @var int Mailing List ID
     */
    public $mailingListId;

    /**
     * @var \DateTime|null Sent
     */
    public $sent;

    /**
     * @var \DateTime|null Failed
     */
    public $failed;

    /**
     * @var \DateTime|null Opened
     */
    public $opened;

    /**
     * @var \DateTime|null Clicked
     */
    public $clicked;

    /**
     * @var \DateTime|null Unsubscribed
     */
    public $unsubscribed;

    /**
     * @var \DateTime|null Complained
     */
    public $complained;

    /**
     * @var \DateTime|null Bounced
     */
    public $bounced;

    /**
     * @var int Opens
     */
    public $opens = 0;

    /**
     * @var int Clicks
     */
    public $clicks = 0;

    /**
     * @var string|null Links
     */
    public $links;

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
     * @var \DateTime
     */
    public $dateUpdated;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function datetimeAttributes(): array
    {
        $attributes = parent::datetimeAttributes();
        $attributes[] = 'sent';
        $attributes[] = 'failed';
        $attributes[] = 'opened';
        $attributes[] = 'clicked';
        $attributes[] = 'unsubscribed';
        $attributes[] = 'complained';
        $attributes[] = 'bounced';

        return $attributes;
    }

    /**
     * Returns the contact
     *
     * @return ContactElement
     */
    public function getContact(): ContactElement
    {
        return Campaign::$plugin->contacts->getContactById($this->contactId);
    }

    /**
     * Returns the campaign
     *
     * @return CampaignElement
     */
    public function getCampaign(): CampaignElement
    {
        return Campaign::$plugin->campaigns->getCampaignById($this->campaignId);
    }

    /**
     * Returns the sendout
     *
     * @return SendoutElement|null
     */
    public function getSendout()
    {
        if ($this->sendoutId === null) {
            return null;
        }

        return Campaign::$plugin->sendouts->getSendoutById($this->sendoutId);
    }

    /**
     * Returns the mailing list
     *
     * @return MailingListElement|null
     */
    public function getMailingList()
    {
        if ($this->mailingListId === null) {
            return null;
        }

        return Campaign::$plugin->mailingLists->getMailingListById($this->mailingListId);
    }

    /**
     * Returns the links as an array
     *
     * @return array
     */
    public function getLinks(): array
    {
        $links = [];
        $linkIds = $this->links ? explode(',', $this->links) : [];

        if (\count($linkIds)) {
            $linkRecords = LinkRecord::find()
                ->where(['id' => $linkIds])
                ->all();

            foreach ($linkRecords as $linkRecord) {
                /** @var LinkRecord $linkRecord */
                $links[] = $linkRecord->url;
            }
        }

        return $links;
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
     * Returns the most significant interaction
     *
     * @return string
     */
    public function getInteraction(): string
    {
        $interactions = ['bounced', 'complained', 'unsubscribed', 'clicked', 'opened'];
        $return = '';

        foreach ($interactions as $interaction) {
            if ($this->$interaction !== null) {
                $return = $this->interaction;
                break;
            }
        }

        return $return;
    }

    /**
     * Returns all interactions
     *
     * @return array
     */
    public function getInteractions(): array
    {
        $interactions = [];

        foreach (self::INTERACTIONS as $interaction) {
            if ($this->$interaction !== null) {
                $interactions[] = $interaction;
            }
        }

        return $interactions;
    }
}