<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\services;

use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\elements\SendoutElement;
use putyourlightson\campaign\events\SubscribeContactEvent;
use putyourlightson\campaign\events\UnsubscribeContactEvent;
use putyourlightson\campaign\events\UpdateContactEvent;
use putyourlightson\campaign\models\ContactCampaignModel;
use putyourlightson\campaign\records\LinkRecord;
use putyourlightson\campaign\records\ContactCampaignRecord;

use DeviceDetector\DeviceDetector;
use GuzzleHttp\Exception\ConnectException;

use Craft;
use craft\base\Component;
use craft\errors\ElementNotFoundException;
use craft\helpers\Json;
use yii\base\Exception;

/**
 * TrackerService
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 */
class TrackerService extends Component
{
    // Constants
    // =========================================================================

    /**
     * @event SubscribeContactEvent
     */
    const EVENT_BEFORE_SUBSCRIBE_CONTACT = 'beforeSubscribeContact';

    /**
     * @event SubscribeContactEvent
     */
    const EVENT_AFTER_SUBSCRIBE_CONTACT = 'afterSubscribeContact';

    /**
     * @event UnsubscribeContactEvent
     */
    const EVENT_BEFORE_UNSUBSCRIBE_CONTACT = 'beforeUnsubscribeContact';

    /**
     * @event UnsubscribeContactEvent
     */
    const EVENT_AFTER_UNSUBSCRIBE_CONTACT = 'afterUnsubscribeContact';

    /**
     * @event UpdateContactEvent
     */
    const EVENT_BEFORE_UPDATE_CONTACT = 'beforeUpdateContact';

    /**
     * @event UpdateContactEvent
     */
    const EVENT_AFTER_UPDATE_CONTACT = 'afterUpdateContact';

    // Properties
    // =========================================================================

    /**
     * @var mixed
     */
    private $_geoIp;

    /**
     * @var DeviceDetector|null
     */
    private $_deviceDetector;

    // Public Methods
    // =========================================================================

    /**
     * Open
     *
     * @param ContactElement $contact
     * @param SendoutElement $sendout
     *
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws \Throwable
     */
    public function open(ContactElement $contact, SendoutElement $sendout)
    {
        // Add contact interaction to campaign
        Campaign::$plugin->campaigns->addContactInteraction($contact, $sendout, 'opened');

        // Update contact activity
        $this->_updateContactActivity($contact);
    }

    /**
     * Click
     *
     * @param ContactElement $contact
     * @param SendoutElement $sendout
     * @param LinkRecord $linkRecord
     *
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws \Throwable
     */
    public function click(ContactElement $contact, SendoutElement $sendout, LinkRecord $linkRecord)
    {
        // Add contact interaction to campaign
        Campaign::$plugin->campaigns->addContactInteraction($contact, $sendout, 'clicked', $linkRecord);

        // Update contact activity
        $this->_updateContactActivity($contact);
    }

    /**
     * Subscribe
     *
     * @param ContactElement $contact
     * @param MailingListElement $mailingList
     * @param string|null $sourceType
     * @param string|null $source
     * @param bool|null $verify
     *
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws \Throwable
     */
    public function subscribe(ContactElement $contact, MailingListElement $mailingList, string $sourceType = null, string $source = null, bool $verify = null)
    {
        $sourceType = $sourceType ?? '';
        $source = $source ?? '';
        $verify = $verify ?? false;

        // Fire a before event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SUBSCRIBE_CONTACT)) {
            $this->trigger(self::EVENT_BEFORE_SUBSCRIBE_CONTACT, new SubscribeContactEvent([
                'contact' => $contact,
                'mailingList' => $mailingList,
                'sourceType' => $sourceType,
                'source' => $source,
            ]));
        }

        Campaign::$plugin->mailingLists->addContactInteraction($contact, $mailingList, 'subscribed', $sourceType, $source, $verify);

        // Update contact activity
        $this->_updateContactActivity($contact);

        // Fire an after event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SUBSCRIBE_CONTACT)) {
            $this->trigger(self::EVENT_AFTER_SUBSCRIBE_CONTACT, new SubscribeContactEvent([
                'contact' => $contact,
                'mailingList' => $mailingList,
                'sourceType' => $sourceType,
                'source' => $source,
            ]));
        }
    }

    /**
     * Unsubscribe
     *
     * @param ContactElement $contact
     * @param SendoutElement $sendout
     *
     * @return MailingListElement|null
     * @throws \Throwable
     * @throws ElementNotFoundException
     * @throws Exception
     */
    public function unsubscribe(ContactElement $contact, SendoutElement $sendout)
    {
        $contactCampaignRecord = ContactCampaignRecord::findOne([
            'contactId' => $contact->id,
            'sendoutId' => $sendout->id,
        ]);

        if ($contactCampaignRecord === null) {
            return null;
        }

        /** @var ContactCampaignModel $contactCampaign */
        $contactCampaign = ContactCampaignModel::populateModel($contactCampaignRecord, false);

        $mailingList = $contactCampaign->getMailingList();

        if ($mailingList !== null) {
            // Fire a before event
            if ($this->hasEventHandlers(self::EVENT_BEFORE_UNSUBSCRIBE_CONTACT)) {
                $this->trigger(self::EVENT_BEFORE_UNSUBSCRIBE_CONTACT, new UnsubscribeContactEvent([
                    'contact' => $contact,
                    'mailingList' => $mailingList,
                ]));
            }

            Campaign::$plugin->mailingLists->addContactInteraction($contact, $mailingList, 'unsubscribed');
        }

        Campaign::$plugin->campaigns->addContactInteraction($contact, $sendout, 'unsubscribed');

        // Update contact activity
        $this->_updateContactActivity($contact);

        // Fire an after event
        if ($mailingList !== null AND $this->hasEventHandlers(self::EVENT_AFTER_UNSUBSCRIBE_CONTACT)) {
            $this->trigger(self::EVENT_AFTER_UNSUBSCRIBE_CONTACT, new UnsubscribeContactEvent([
                'contact' => $contact,
                'mailingList' => $mailingList,
            ]));
        }

        return $mailingList;
    }

    /**
     * Updates a contact
     *
     * @param ContactElement $contact
     *
     * @return bool
     */
    public function updateContact(ContactElement $contact): bool
    {
        // Fire a before event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_UPDATE_CONTACT)) {
            $this->trigger(self::EVENT_BEFORE_UPDATE_CONTACT, new UpdateContactEvent([
                'contact' => $contact,
            ]));
        }

        if (!Craft::$app->getElements()->saveElement($contact)) {
            return false;
        }

        // Update contact activity
        $this->_updateContactActivity($contact);

        // Fire an after event
        if ($this->hasEventHandlers(self::EVENT_AFTER_UPDATE_CONTACT)) {
            $this->trigger(self::EVENT_AFTER_UPDATE_CONTACT, new UpdateContactEvent([
                'contact' => $contact,
            ]));
        }

        return true;
    }

    // Private Methods
    // =========================================================================

    /**
     * Update contact activity
     *
     * @param ContactElement $contact
     *
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws \Throwable
     */
    private function _updateContactActivity(ContactElement $contact)
    {
        $contact->lastActivity = new \DateTime();

        // Get GeoIP if enabled
        if (Campaign::$plugin->getSettings()->geoIp) {
            if ($this->_geoIp === null) {
                $this->_geoIp = $this->_getGeoIp();
            }

            // If country exists
            if (!empty($this->_geoIp['countryName'])) {
                $contact->country = $this->_geoIp['countryName'];
                $contact->geoIp = $this->_geoIp;
            }
        }

        // Get device detector
        if ($this->_deviceDetector === null) {
            $userAgent = Craft::$app->getRequest()->getUserAgent();
            $this->_deviceDetector = new DeviceDetector($userAgent);
        }

        $this->_deviceDetector->parse();
        $device = $this->_deviceDetector->getDeviceName();

        // If device exists and not a bot
        if ($device AND !$this->_deviceDetector->isBot()) {
            $contact->device = $device;

            $os = $this->_deviceDetector->getOs('name');
            $contact->os = $os == DeviceDetector::UNKNOWN ? '' : $os;

            $client = $this->_deviceDetector->getClient('name');
            $contact->client = $client == DeviceDetector::UNKNOWN ? '' : $client;
        }

        Craft::$app->getElements()->saveElement($contact);
    }

    /**
     * Gets geolocation based on IP address
     *
     * @param int|null
     *
     * @return array|null
     */
    private function _getGeoIp(int $timeout = null)
    {
        $timeout = $timeout ?? 5;

        $geoIp = null;

        $client = Craft::createGuzzleClient([
            'timeout' => $timeout,
            'connect_timeout' => $timeout,
        ]);

        try {
            $ip = Craft::$app->getRequest()->getUserIP();
            $apiKey = Craft::parseEnv(Campaign::$plugin->getSettings()->ipstackApiKey);

            $response = $client->get('http://api.ipstack.com/'.$ip.'?access_key='.$apiKey);

            if ($response->getStatusCode() == 200) {
                $geoIp = Json::decodeIfJson($response->getBody());
            }
        }
        catch (ConnectException $e) {}

        // If country is empty then return null
        if (empty($geoIp['country_code'])) {
            return null;
        }

        return [
            'continentCode' => $geoIp['continent_code'] ?? '',
            'continentName' => $geoIp['continent_name'] ?? '',
            'countryCode' => $geoIp['country_code'] ?? '',
            'countryName' => $geoIp['country_name'] ?? '',
            'regionCode' => $geoIp['region_code'] ?? '',
            'regionName' => $geoIp['region_name'] ?? '',
            'city' => $geoIp['city'] ?? '',
            'postCode' => $geoIp['zip_code'] ?? '',
            'timeZone' => $geoIp['time_zone']['id'] ?? '',
        ];
    }
}