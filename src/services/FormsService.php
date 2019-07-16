<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\services;

use DateTime;
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
use Throwable;
use yii\base\Exception;

/**
 * FormsService
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.10.0
 */
class FormsService extends Component
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

    // Public Methods
    // =========================================================================

    /**
     * Subscribe contact
     *
     * @param ContactElement $contact
     * @param MailingListElement $mailingList
     * @param string|null $sourceType
     * @param string|null $source
     * @param bool|null $verify
     *
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws Throwable
     */
    public function subscribeContact(ContactElement $contact, MailingListElement $mailingList, string $sourceType = null, string $source = null, bool $verify = null)
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
        ContactHelper::updateContactActivity($contact);

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
        ContactHelper::updateContactActivity($contact);

        // Fire an after event
        if ($this->hasEventHandlers(self::EVENT_AFTER_UPDATE_CONTACT)) {
            $this->trigger(self::EVENT_AFTER_UPDATE_CONTACT, new UpdateContactEvent([
                'contact' => $contact,
            ]));
        }

        return true;
    }

    /**
     * Unsubscribes a contact
     *
     * @param ContactElement $contact
     * @param MailingListElement[] $mailingLists
     *
     * @return bool
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws Throwable
     */
    public function unsubscribeContact(ContactElement $contact, array $mailingLists): bool
    {
        foreach ($mailingLists as $mailingList) {
            // Fire a before event
            if ($this->hasEventHandlers(self::EVENT_BEFORE_UNSUBSCRIBE_CONTACT)) {
                $this->trigger(self::EVENT_BEFORE_UNSUBSCRIBE_CONTACT, new UnsubscribeContactEvent([
                    'contact' => $contact,
                    'mailingList' => $mailingList,
                ]));
            }

            Campaign::$plugin->mailingLists->addContactInteraction($contact, $mailingList, 'unsubscribed');

            // Fire an after event
            if ($mailingList !== null AND $this->hasEventHandlers(self::EVENT_AFTER_UNSUBSCRIBE_CONTACT)) {
                $this->trigger(self::EVENT_AFTER_UNSUBSCRIBE_CONTACT, new UnsubscribeContactEvent([
                    'contact' => $contact,
                    'mailingList' => $mailingList,
                ]));
            }
        }

        // Update contact activity
        ContactHelper::updateContactActivity($contact);

        return true;
    }
}
