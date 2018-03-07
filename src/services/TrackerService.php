<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\services;

use craft\errors\ElementNotFoundException;
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

        // Update contact mailing list record
        $this->_updateContactMailingListRecord($contact, $mailingList);

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

        // Update GeoIP if it exists
        $geoIp = $this->_getGeoIp();

        if ($geoIp !== null) {
            $contactCampaignRecord->country = GeoIpHelper::getCountryName($geoIp);
            $contactCampaignRecord->geoIp = $geoIp;
        }

        // Update device, OS and client
        $deviceDetector = $this->_getDeviceDetector();
        $contactCampaignRecord->device = $deviceDetector->getDeviceName();

        $os = $deviceDetector->getOs('name');
        $contactCampaignRecord->os = $os == DeviceDetector::UNKNOWN ? '' : $os;
        $client = $deviceDetector->getClient('name');
        $contactCampaignRecord->client = $client == DeviceDetector::UNKNOWN ? '' : $client;

        $contactCampaignRecord->save();
    }

    /**
     * Updates a contact mailing list record
     *
     * @param ContactElement $contact
     * @param MailingListElement $mailingList
     */
    private function _updateContactMailingListRecord(ContactElement $contact, MailingListElement $mailingList)
    {
        $contactMailingListRecord = ContactMailingListRecord::findOne([
            'contactId' => $contact->id,
            'mailingListId' => $mailingList->id,
        ]);

        // Ensure record exists
        if ($contactMailingListRecord === null) {
            return;
        }

        // Update GeoIP if it exists
        $geoIp = $this->_getGeoIp();

        if ($geoIp !== null) {
            $contactMailingListRecord->country = GeoIpHelper::getCountryName($geoIp);
            $contactMailingListRecord->geoIp = $geoIp;
        }

        // Update browser, device and user agent
        $deviceDetector = $this->_getDeviceDetector();
        $contactMailingListRecord->device = $deviceDetector->getDeviceName();
        $contactMailingListRecord->os = $deviceDetector->getOs('name');
        $contactMailingListRecord->client = $deviceDetector->getClient('name');

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
        $geoIp = $this->_getGeoIp();

        if ($geoIp !== null) {
            $contact->country = GeoIpHelper::getCountryName($this->_geoIp);
            $contact->geoIp = $this->_geoIp;
            $contact->lastActivity = new \DateTime();
        }

        // Update browser, device and user agent
        $deviceDetector = $this->_getDeviceDetector();
        $contact->device = $deviceDetector->getDeviceName();
        $contact->os = $deviceDetector->getOs('name');
        $contact->client = $deviceDetector->getClient('name');

        Craft::$app->getElements()->saveElement($contact);
    }

    /**
     * Gets geolocation
     *
     * @return string
     */
    private function _getGeoIp(): string
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