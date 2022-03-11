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
    public function open(ContactElement $contact, SendoutElement $sendout)
    {
        // Add contact interaction to campaign
        Campaign::$plugin->campaigns->addContactInteraction($contact, $sendout, 'opened');

        // Update contact activity
        ContactActivityHelper::updateContactActivity($contact);
    }

    /**
     * Tracks a click.
     */
    public function click(ContactElement $contact, SendoutElement $sendout, LinkRecord $linkRecord)
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
}
