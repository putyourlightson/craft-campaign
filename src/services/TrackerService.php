<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\services;

use craft\base\Component;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\elements\SendoutElement;
use putyourlightson\campaign\events\UnsubscribeContactEvent;
use putyourlightson\campaign\helpers\ContactActivityHelper;
use putyourlightson\campaign\models\ContactCampaignModel;
use putyourlightson\campaign\records\ContactCampaignRecord;
use putyourlightson\campaign\records\LinkRecord;

class TrackerService extends Component
{
    /**
     * @event UnsubscribeContactEvent
     */
    public const EVENT_BEFORE_UNSUBSCRIBE_CONTACT = 'beforeUnsubscribeContact';

    /**
     * @event UnsubscribeContactEvent
     */
    public const EVENT_AFTER_UNSUBSCRIBE_CONTACT = 'afterUnsubscribeContact';

    /**
     * Tracks an open.
     */
    public function open(ContactElement $contact, SendoutElement $sendout): void
    {
        // Add contact interaction to campaign
        Campaign::$plugin->campaigns->addContactInteraction($contact, $sendout, 'opened');

        // Update contact activity
        ContactActivityHelper::updateContactActivity($contact);
    }

    /**
     * Tracks a click.
     */
    public function click(ContactElement $contact, SendoutElement $sendout, LinkRecord $linkRecord): void
    {
        // Add contact interaction to campaign
        Campaign::$plugin->campaigns->addContactInteraction($contact, $sendout, 'clicked', $linkRecord);

        // Update contact activity
        ContactActivityHelper::updateContactActivity($contact);
    }

    /**
     * Tracks an unsubscribe.
     */
    public function unsubscribe(ContactElement $contact, SendoutElement $sendout): ?MailingListElement
    {
        $contactCampaign = $this->getContactCampaign($contact, $sendout);
        if ($contactCampaign === null) {
            return null;
        }

        $mailingList = $contactCampaign->getMailingList();
        $mailingLists = $mailingList === null ? [] : [$mailingList];
        $this->processUnsubscribe($contact, $sendout, $mailingLists);

        return $mailingList;
    }

    /**
     * Tracks an unsubscribe from all mailing lists.
     *
     * @since 3.9.0
     */
    public function unsubscribeAll(ContactElement $contact, SendoutElement $sendout): void
    {
        if ($this->getContactCampaign($contact, $sendout) === null) {
            return;
        }

        $this->processUnsubscribe($contact, $sendout, $contact->getSubscribedMailingLists());
    }

    /**
     * Returns the contact campaign for the provided contact and sendout.
     *
     * @since 3.9.0
     */
    private function getContactCampaign(ContactElement $contact, SendoutElement $sendout): ?ContactCampaignModel
    {
        /** @var ContactCampaignRecord|null $contactCampaignRecord */
        $contactCampaignRecord = ContactCampaignRecord::find()
            ->where([
                'contactId' => $contact->id,
                'sendoutId' => $sendout->id,
            ])
            ->one();

        if ($contactCampaignRecord === null) {
            return null;
        }

        $contactCampaign = new ContactCampaignModel();
        $contactCampaign->setAttributes($contactCampaignRecord->getAttributes(), false);

        return $contactCampaign;
    }

    /**
     * Tracks an unsubscribe from the provided mailing lists.
     *
     * @param MailingListElement[] $mailingLists
     * @since 3.9.0
     */
    private function processUnsubscribe(ContactElement $contact, SendoutElement $sendout, array $mailingLists): void
    {
        foreach ($mailingLists as $subscribedMailingList) {
            // Fire a before event
            if ($this->hasEventHandlers(self::EVENT_BEFORE_UNSUBSCRIBE_CONTACT)) {
                $this->trigger(self::EVENT_BEFORE_UNSUBSCRIBE_CONTACT, new UnsubscribeContactEvent([
                    'contact' => $contact,
                    'mailingList' => $subscribedMailingList,
                ]));
            }

            Campaign::$plugin->mailingLists->addContactInteraction($contact, $subscribedMailingList, 'unsubscribed');
        }

        Campaign::$plugin->campaigns->addContactInteraction($contact, $sendout, 'unsubscribed');

        // Update contact activity
        ContactActivityHelper::updateContactActivity($contact);

        // Fire after events
        if ($this->hasEventHandlers(self::EVENT_AFTER_UNSUBSCRIBE_CONTACT)) {
            foreach ($mailingLists as $subscribedMailingList) {
                $this->trigger(self::EVENT_AFTER_UNSUBSCRIBE_CONTACT, new UnsubscribeContactEvent([
                    'contact' => $contact,
                    'mailingList' => $subscribedMailingList,
                ]));
            }
        }
    }
}
