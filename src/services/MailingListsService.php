<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\services;

use craft\base\Component;
use DateTime;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\models\ContactMailingListModel;
use putyourlightson\campaign\records\ContactMailingListRecord;

/**
 * @property-read MailingListElement[] $allMailingLists
 */
class MailingListsService extends Component
{
    /**
     * Returns a mailing list by ID.
     */
    public function getMailingListById(int $mailingListId): ?MailingListElement
    {
        return MailingListElement::find()
            ->id($mailingListId)
            ->site('*')
            ->status(null)
            ->one();
    }

    /**
     * Returns an array of mailing lists by IDs.
     *
     * @param int[] $mailingListIds
     * @return MailingListElement[]
     */
    public function getMailingListsByIds(array $mailingListIds): array
    {
        if (empty($mailingListIds)) {
            return [];
        }

        return MailingListElement::find()
            ->id($mailingListIds)
            ->site('*')
            ->status(null)
            ->all();
    }

    /**
     * Returns a mailing list by its slug, in the current site.
     */
    public function getMailingListBySlug(string $mailingListSlug): ?MailingListElement
    {
        return MailingListElement::find()
            ->slug($mailingListSlug)
            ->one();
    }

    /**
     * Returns all mailing lists.
     *
     * @return MailingListElement[]
     */
    public function getAllMailingLists(): array
    {
        return MailingListElement::find()
            ->site('*')
            ->all();
    }

    /**
     * Adds a contact interaction.
     */
    public function addContactInteraction(ContactElement $contact, MailingListElement $mailingList, string $interaction, string $sourceType = '', int|string $source = '', bool $verify = false)
    {
        // Ensure that interaction exists
        if (!in_array($interaction, ContactMailingListModel::INTERACTIONS)) {
            return;
        }

        /** @var ContactMailingListRecord|null $contactMailingListRecord */
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
        if ($contactMailingListRecord->{$interaction} === null) {
            $contactMailingListRecord->{$interaction} = new DateTime();
        }

        // If subscribing
        if ($interaction == 'subscribed') {
            // Set source and source URL if not already set
            $contactMailingListRecord->sourceType = $contactMailingListRecord->sourceType ?? $sourceType;
            $contactMailingListRecord->source = $contactMailingListRecord->source ?? $source;

            if ($verify && $contactMailingListRecord->verified === null) {
                $contactMailingListRecord->verified = new DateTime();
            }
        }

        $contactMailingListRecord->subscriptionStatus = $interaction;
        $contactMailingListRecord->save();
    }

    /**
     * Deletes a contact's subscription to a mailing list.
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
