<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\services;

use Craft;
use craft\base\Component;
use craft\errors\ElementNotFoundException;
use DateTime;

use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\records\ContactCampaignRecord;
use Throwable;
use yii\base\Exception;

class WebhookService extends Component
{
    /**
     * Marks a contact as complained.
     */
    public function complain(ContactElement $contact)
    {
        Campaign::$plugin->log('Contact {email} marked as "complained".', ['email' => $contact->email]);

        $this->_addInteraction($contact, 'complained');
    }

    /**
     * Marks a contact as bounced.
     */
    public function bounce(ContactElement $contact)
    {
        Campaign::$plugin->log('Contact {email} marked as "bounced".', ['email' => $contact->email]);

        $this->_addInteraction($contact, 'bounced');
    }

    /**
     * Marks a contact as unsubscribed.
     */
    public function unsubscribe(ContactElement $contact)
    {
        Campaign::$plugin->log('Contact {email} marked as "unsubscribed".', ['email' => $contact->email]);

        $this->_addInteraction($contact, 'unsubscribed');
    }

    /**
     * Adds an interaction.
     */
    private function _addInteraction(ContactElement $contact, string $interaction)
    {
        // Get all contact campaigns
        $contactCampaignRecords = ContactCampaignRecord::find()
            ->where(['contactId' => $contact->id])
            ->all();

        /** @var ContactCampaignRecord $contactCampaignRecord */
        foreach ($contactCampaignRecords as $contactCampaignRecord) {
            $mailingList = Campaign::$plugin->mailingLists->getMailingListById($contactCampaignRecord->mailingListId);

            if ($mailingList !== null) {
                // Add contact interaction to mailing list
                Campaign::$plugin->mailingLists->addContactInteraction($contact, $mailingList, $interaction);
            }

            $sendout = Campaign::$plugin->sendouts->getSendoutById($contactCampaignRecord->sendoutId);

            if ($sendout !== null) {
                // Add contact interaction to campaign
                Campaign::$plugin->campaigns->addContactInteraction($contact, $sendout, $interaction);
            }
        }

        // Update contact
        if (isset($contact->{$interaction}) && $contact->{$interaction} === null) {
            $contact->{$interaction} = new DateTime();

            Craft::$app->getElements()->saveElement($contact);
        }
    }
}
