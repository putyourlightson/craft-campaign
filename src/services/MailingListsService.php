<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\services;

use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\models\ContactMailingListModel;
use putyourlightson\campaign\records\ContactMailingListRecord;

use craft\base\Component;

/**
 * MailingListsService
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0   
 */
class MailingListsService extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * Returns mailing list by ID
     *
     * @param int $mailingListId
     *
     * @return MailingListElement|null
     */
    public function getMailingListById(int $mailingListId)
    {
        if (!$mailingListId) {
            return null;
        }

        $mailingList = MailingListElement::find()
            ->id($mailingListId)
            ->status(null)
            ->one();

        return $mailingList;
    }

    /**
     * Adds a contact interaction
     *
     * @param ContactElement $contact
     * @param MailingListElement $mailingList
     * @param string $interaction
     * @param string|null $sourceType
     * @param string|null $source
     */
    public function addContactInteraction(ContactElement $contact, MailingListElement $mailingList, string $interaction, $sourceType = '', $source = '')
    {
        // Ensure that interaction exists
        if (!\in_array($interaction, ContactMailingListModel::INTERACTIONS, true)) {
            return;
        }

        $contactMailingListRecord = ContactMailingListRecord::find()
            ->where([
                'contactId' => $contact->id,
                'mailingListId' => $mailingList->id,
            ])
            ->one();

        if ($contactMailingListRecord === null) {
            $contactMailingListRecord = new ContactMailingListRecord();
            $contactMailingListRecord->contactId = $contact->id;
            $contactMailingListRecord->mailingListId = $mailingList->id;
        }

        // If first time for this interaction
        if ($contactMailingListRecord->$interaction === null) {
            $contactMailingListRecord->$interaction = new \DateTime();
        }

        // If subscribing
        if ($interaction == 'subscribed') {
            // Set source and source URL if not already set
            $contactMailingListRecord->sourceType = $contactMailingListRecord->sourceType ?? $sourceType;
            $contactMailingListRecord->source = $contactMailingListRecord->source ?? $source;
        }

        $contactMailingListRecord->subscriptionStatus = $interaction;
        $contactMailingListRecord->save();
    }

    /**
     * Deletes a contact's subscription to a mailing list
     *
     * @param ContactElement $contact
     * @param MailingListElement $mailingList
     *
     * @throws \Exception|\Throwable in case delete failed.
     */
    public function deleteContactSubscription(ContactElement $contact, MailingListElement $mailingList)
    {
        $contactMailingListRecord = ContactMailingListRecord::find()
            ->where([
                'contactId' => $contact->id,
                'mailingListId' => $mailingList->id,
            ])
            ->one();

        if ($contactMailingListRecord !== null) {
            $contactMailingListRecord->delete();
        }
    }
}