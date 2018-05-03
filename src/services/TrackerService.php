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
use putyourlightson\campaign\models\ContactCampaignModel;
use putyourlightson\campaign\records\LinkRecord;
use putyourlightson\campaign\records\ContactCampaignRecord;
use putyourlightson\campaign\records\ContactMailingListRecord;

use DeviceDetector\DeviceDetector;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;

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

        // Update contact campaign record
        $this->_updateContactCampaignRecord($contact, $sendout);

        // Update contact
        $this->_updateContact($contact);
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

        // Update contact campaign record
        $this->_updateContactCampaignRecord($contact, $sendout);

        // Update contact
        $this->_updateContact($contact);
    }

    /**
     * Subscribe
     *
     * @param ContactElement $contact
     * @param MailingListElement $mailingList
     * @param string|null $sourceType
     * @param string|null $source
     *
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws \Throwable
     */
    public function subscribe(ContactElement $contact, MailingListElement $mailingList, $sourceType = '', $source = '')
    {
        Campaign::$plugin->mailingLists->addContactInteraction($contact, $mailingList, 'subscribed', $sourceType, $source);

        // Update contact mailing list record
        $this->_updateContactMailingListRecord($contact, $mailingList, true);

        // Update contact
        $this->_updateContact($contact);
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
            /** @var MailingListElement $mailingList */
            Campaign::$plugin->mailingLists->addContactInteraction($contact, $mailingList, 'unsubscribed');

            $this->_updateContactMailingListRecord($contact, $mailingList);
        }

        Campaign::$plugin->campaigns->addContactInteraction($contact, $sendout, 'unsubscribed');

        $this->_updateContactCampaignRecord($contact, $sendout);

        $this->_updateContact($contact);

        return $mailingList;
    }

    // Private Methods
    // =========================================================================

    /**
     * Updates a contact campaign record
     *
     * @param ContactElement $contact
     * @param SendoutElement $sendout
     */
    private function _updateContactCampaignRecord(ContactElement $contact, SendoutElement $sendout)
    {
        $contactCampaignRecord = ContactCampaignRecord::findOne([
            'contactId' => $contact->id,
            'sendoutId' => $sendout->id,
        ]);

        if ($contactCampaignRecord === null) {
            return;
        }

        /** @var ContactCampaignRecord $contactCampaignRecord */
        $contactCampaignRecord = $this->_updateLocationDevice($contactCampaignRecord);

        $contactCampaignRecord->save();
    }

    /**
     * Updates a contact mailing list record
     *
     * @param ContactElement $contact
     * @param MailingListElement $mailingList
     * @param bool $verify
     */
    private function _updateContactMailingListRecord(ContactElement $contact, MailingListElement $mailingList, $verify = false)
    {
        $contactMailingListRecord = ContactMailingListRecord::findOne([
            'contactId' => $contact->id,
            'mailingListId' => $mailingList->id,
        ]);

        // Ensure record exists
        if ($contactMailingListRecord === null) {
            return;
        }

        if ($verify AND $contactMailingListRecord->verified === null) {
            $contactMailingListRecord->verified = new \DateTime();
        }

        $contactMailingListRecord = $this->_updateLocationDevice($contactMailingListRecord);

        /** @var ContactMailingListRecord $contactMailingListRecord */
        $contactMailingListRecord->save();
    }

    /**
     * Update contact
     *
     * @param ContactElement $contact
     *
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws \Throwable
     */
    private function _updateContact(ContactElement $contact)
    {
        $contact->lastActivity = new \DateTime();

        $contact = $this->_updateLocationDevice($contact);

        /** @var ContactElement $contact */
        Craft::$app->getElements()->saveElement($contact);
    }

    /**
     * Update location and device
     *
     * @param ContactElement|ContactCampaignRecord|ContactMailingListRecord $model
     *
     * @return ContactElement|ContactCampaignRecord|ContactMailingListRecord
     */
    private function _updateLocationDevice($model)
    {
        // Get GeoIP
        if ($this->_geoIp === null) {
            $this->_geoIp = $this->getGeoIp();
        }

        // If GeoIP exists
        if ($this->_geoIp !== null) {
            $country = $this->_geoIp['countryName'];

            // If country exists
            if ($country) {
                $model->country = $country;
                $model->geoIp = $this->_geoIp;
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
            $model->device = $device;

            $os = $this->_deviceDetector->getOs('name');
            $model->os = $os == DeviceDetector::UNKNOWN ? '' : $os;

            $client = $this->_deviceDetector->getClient('name');
            $model->client = $client == DeviceDetector::UNKNOWN ? '' : $client;
        }

        return $model;
    }


    /**
     * Gets geolocation based on IP address
     *
     * @param int|null
     *
     * @return array|null
     */
    private function getGeoIp(int $timeout = 3)
    {
        $geoIp = null;

        $client = new Client([
            'timeout' => $timeout,
            'connect_timeout' => $timeout,
        ]);

        $ipAddress = Craft::$app->request->getUserIP();

        try {
            $response = $client->request('get', 'http://freegeoip.net/json/'.$ipAddress);

            if ($response->getStatusCode() == 200) {
                $geoIp = $response->getBody();
                $geoIp = Json::decodeIfJson($geoIp);
            }
        }
        catch (ConnectException $e) {}
        catch (GuzzleException $e) {}

        // If country is empty then return null
        if (empty($geoIp['country_code'])) {
            return null;
        }

        return [
            'city' => $geoIp['city'] ?? '',
            'postCode' => $geoIp['zip_code'] ?? '',
            'regionCode' => $geoIp['region_code'] ?? '',
            'regionName' => $geoIp['region_name'] ?? '',
            'countryCode' => $geoIp['country_code'] ?? '',
            'countryName' => $geoIp['country_name'] ?? '',
            'timeZone' => $geoIp['time_zone'] ?? '',
        ];
    }
}