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
use putyourlightson\campaign\helpers\GeoIpHelper;
use putyourlightson\campaign\records\LinkRecord;
use putyourlightson\campaign\records\ContactCampaignRecord;
use putyourlightson\campaign\records\ContactMailingListRecord;
use DeviceDetector\DeviceDetector;

use Craft;
use craft\base\Component;
use craft\errors\ElementNotFoundException;
use yii\base\Exception;
use yii\base\Model;

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
     * @var string|null
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
     * @param LinkRecord     $linkRecord
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
     * @param ContactElement     $contact
     * @param MailingListElement $mailingList
     * @param bool|null          $pending
     * @param string|null        $source
     * @param string|null        $sourceUrl
     *
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws \Throwable
     */
    public function subscribe(ContactElement $contact, MailingListElement $mailingList, bool $pending = true, string $source = '', string $sourceUrl = '')
    {
        // Add contact interaction to mailing list
        $interaction = $pending ? 'pending' : 'subscribed';
        Campaign::$plugin->mailingLists->addContactInteraction($contact, $mailingList, $interaction, $source, $sourceUrl);

        if (!$pending) {
            // Update contact mailing list record
            $this->_updateContactMailingListRecord($contact, $mailingList, true);

            // Update contact
            $this->_updateContact($contact, true);
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
        // Get first mailing list in sendout that this contact is subscribed to
        $mailingList = $contact->getSubscribedMailingListInSendout($sendout);

        // If we found a mailing list
        if ($mailingList !== null) {
            // Add contact interaction to mailing list
            Campaign::$plugin->mailingLists->addContactInteraction($contact, $mailingList, 'unsubscribed');

            // Update contact mailing list record
            $this->_updateContactMailingListRecord($contact, $mailingList);
        }

        // Add contact interaction to campaign
        Campaign::$plugin->campaigns->addContactInteraction($contact, $sendout, 'unsubscribed');

        // Update contact campaign record
        $this->_updateContactCampaignRecord($contact, $sendout);

        // Update contact
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
            'campaignId' => $sendout->campaignId,
        ]);

        // Ensure contact campaign record exists
        if ($contactCampaignRecord === null) {
            return;
        }

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

        $contactMailingListRecord->save();
    }

    /**
     * Update contact
     *
     * @param ContactElement $contact
     * @param bool $verify
     *
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws \Throwable
     */
    private function _updateContact(ContactElement $contact, $verify = false)
    {
        if ($verify AND $contact->pending) {
            $contact->pending = false;
            $contact->verified = new \DateTime();
        }

        $contact->lastActivity = new \DateTime();

        $contact = $this->_updateLocationDevice($contact);

        Craft::$app->getElements()->saveElement($contact);
    }

    /**
     * Update location and device
     *
     * @param Model $model
     *
     * @return Model
     */
    private function _updateLocationDevice(Model $model): Model
    {
        // Update GeoIP if it exists
        $geoIp = $this->_getGeoIp();

        if ($geoIp !== null) {
            $model->country = GeoIpHelper::getCountryName($this->_geoIp);
            $model->geoIp = $this->_geoIp;
        }

        // Get device detector
        $deviceDetector = $this->_getDeviceDetector();
        $device = $deviceDetector->getDeviceName();

        // If device exists and not a bot
        if ($device AND !$deviceDetector->isBot()) {
            // Update device, OS and client
            $model->device = $device;

            $os = $deviceDetector->getOs('name');
            $model->os = $os == DeviceDetector::UNKNOWN ? '' : $os;

            $client = $deviceDetector->getClient('name');
            $model->client = $client == DeviceDetector::UNKNOWN ? '' : $client;
        }

        return $model;
    }

    /**
     * Gets geolocation
     *
     * @return string|null
     */
    private function _getGeoIp()
    {
        if ($this->_geoIp !== null) {
            return $this->_geoIp;
        }

        $this->_geoIp = GeoIpHelper::getGeoIp();

        return $this->_geoIp;
    }

    /**
     * Gets device detector
     *
     * @return DeviceDetector
     */
    private function _getDeviceDetector(): DeviceDetector
    {
        if ($this->_deviceDetector !== null) {
            return $this->_deviceDetector;
        }

        $userAgent = Craft::$app->getRequest()->getUserAgent();
        $this->_deviceDetector = new DeviceDetector($userAgent);
        $this->_deviceDetector->parse();

        return $this->_deviceDetector;
    }
}