<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\services;

use craft\errors\DeprecationException;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\elements\SendoutElement;
use putyourlightson\campaign\events\UnsubscribeContactEvent;
use putyourlightson\campaign\helpers\ContactActivityHelper;
use putyourlightson\campaign\models\ContactCampaignModel;
use putyourlightson\campaign\records\LinkRecord;
use putyourlightson\campaign\records\ContactCampaignRecord;

use Craft;
use craft\base\Component;
use Throwable;

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
     * @deprecated in 1.10.0
     */
    const EVENT_BEFORE_SUBSCRIBE_CONTACT = 'beforeSubscribeContact';

    /**
     * @event SubscribeContactEvent
     * @deprecated in 1.10.0
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
     * @deprecated in 1.10.0
     */
    const EVENT_BEFORE_UPDATE_CONTACT = 'beforeUpdateContact';

    /**
     * @event UpdateContactEvent
     * @deprecated in 1.10.0
     */
    const EVENT_AFTER_UPDATE_CONTACT = 'afterUpdateContact';

    // Public Methods
    // =========================================================================

    /**
     * Open
     *
     * @param ContactElement $contact
     * @param SendoutElement $sendout
     * @throws Throwable
     */
    public function open(ContactElement $contact, SendoutElement $sendout)
    {
        // Add contact interaction to campaign
        Campaign::$plugin->campaigns->addContactInteraction($contact, $sendout, 'opened');

        // Update contact activity
        ContactActivityHelper::updateContactActivity($contact);
    }

    /**
     * Click
     *
     * @param ContactElement $contact
     * @param SendoutElement $sendout
     * @param LinkRecord $linkRecord
     * @throws Throwable
     */
    public function click(ContactElement $contact, SendoutElement $sendout, LinkRecord $linkRecord)
    {
        // Add contact interaction to campaign
        Campaign::$plugin->campaigns->addContactInteraction($contact, $sendout, 'clicked', $linkRecord);

        // Update contact activity
        ContactActivityHelper::updateContactActivity($contact);
    }

    /**
     * Unsubscribe
     *
     * @param ContactElement $contact
     * @param SendoutElement $sendout
     * @return MailingListElement|null
     * @throws Throwable
     */
    public function unsubscribe(ContactElement $contact, SendoutElement $sendout)
    {
        $contactCampaignRecord = ContactCampaignRecord::find()
            ->where([
                'contactId' => $contact->id,
                'sendoutId' => $sendout->id,
            ])
            ->one();

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
        ContactActivityHelper::updateContactActivity($contact);

        // Fire an after event
        if ($mailingList !== null && $this->hasEventHandlers(self::EVENT_AFTER_UNSUBSCRIBE_CONTACT)) {
            $this->trigger(self::EVENT_AFTER_UNSUBSCRIBE_CONTACT, new UnsubscribeContactEvent([
                'contact' => $contact,
                'mailingList' => $mailingList,
            ]));
        }

        return $mailingList;
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
     * @throws DeprecationException
     * @deprecated in 1.10.0. Use [[FormsService::subscribeContact()]] instead.
     */
    public function subscribe(ContactElement $contact, MailingListElement $mailingList, string $sourceType = null, string $source = null, bool $verify = null)
    {
        Craft::$app->getDeprecator()->log('TrackerService::subscribe()', 'The “TrackerService::subscribe()” method has been deprecated. Use “FormsService::subscribeContact()” instead.');

        Campaign::$plugin->forms->subscribeContact($contact, $mailingList, $sourceType, $source, $verify);
    }

    /**
     * Updates a contact
     *
     * @param ContactElement $contact
     *
     * @return bool
     * @throws DeprecationException
     * @deprecated in 1.10.0. Use [[FormsService::updateContact()]] instead.
     */
    public function updateContact(ContactElement $contact): bool
    {
        Craft::$app->getDeprecator()->log('TrackerService::updateContact()', 'The “TrackerService::updateContact()” method has been deprecated. Use “FormsService::updateContact()” instead.');

        return Campaign::$plugin->forms->updateContact($contact);
    }
}
