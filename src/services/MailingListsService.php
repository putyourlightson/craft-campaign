<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\services;

use Craft;
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
        /** @var MailingListElement|null */
        return MailingListElement::find()
            ->id($mailingListId)
            ->site('*')
            ->status(null)
            ->one();
    }

    /**
     * Returns an array of mailing lists by IDs.
     *
     * @param int[]|null $mailingListIds
     * @return MailingListElement[]
     */
    public function getMailingListsByIds(?array $mailingListIds): array
    {
        if (empty($mailingListIds)) {
            return [];
        }

        /** @var MailingListElement[] */
        return MailingListElement::find()
            ->id($mailingListIds)
            ->site('*')
            ->status(null)
            ->fixedOrder()
            ->all();
    }

    /**
     * Returns a mailing list by its slug, in the provided site or the current site.
     */
    public function getMailingListBySlug(string $mailingListSlug, ?int $siteId = null): ?MailingListElement
    {
        if ($siteId === null) {
            $siteId = Craft::$app->getSites()->getCurrentSite()->id;
        }

        /** @var MailingListElement|null */
        return MailingListElement::find()
            ->slug($mailingListSlug)
            ->siteId($siteId)
            ->one();
    }

    /**
     * Returns all mailing lists.
     *
     * @return MailingListElement[]
     */
    public function getAllMailingLists(): array
    {
        /** @var MailingListElement[]] */
        return MailingListElement::find()
            ->site('*')
            ->all();
    }

    /**
     * Adds a contact interaction.
     */
    public function addContactInteraction(ContactElement $contact, MailingListElement $mailingList, string $interaction, string $sourceType = '', int|string $source = '', bool $verify = false): void
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
    public function deleteContactSubscription(ContactElement $contact, MailingListElement $mailingList): void
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
