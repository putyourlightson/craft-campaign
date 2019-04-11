<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\services;

use Craft;
use craft\base\Component;
use craft\records\Element_SiteSettings;
use DateTime;
use Exception;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\models\ContactMailingListModel;
use putyourlightson\campaign\records\ContactMailingListRecord;
use Throwable;

/**
 * MailingListsService
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 *
 * @property MailingListElement[] $allMailingLists
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
        // Get site ID from element site settings
        $siteId = Element_SiteSettings::find()
            ->select('siteId')
            ->where(['elementId' => $mailingListId])
            ->scalar();

        if ($siteId === null) {
            return null;
        }

        $mailingList = MailingListElement::find()
            ->id($mailingListId)
            ->siteId($siteId)
            ->status(null)
            ->one();

        return $mailingList;
    }

    /**
     * Returns mailing lists by IDs
     *
     * @param int $siteId
     * @param int[] $mailingListIds
     *
     * @return MailingListElement[]
     */
    public function getMailingListsByIds(int $siteId, array $mailingListIds): array
    {
        if (empty($mailingListIds)) {
            return [];
        }

        return MailingListElement::find()
            ->id($mailingListIds)
            ->siteId($siteId)
            ->status(null)
            ->all();
    }

    /**
     * Returns mailing list by slug
     *
     * @param string $mailingListSlug
     *
     * @return MailingListElement|null
     */
    public function getMailingListBySlug(string $mailingListSlug)
    {
        return MailingListElement::find()
            ->slug($mailingListSlug)
            ->one();
    }

    /**
     * Returns all mailing lists across all sites
     *
     * @return MailingListElement[]
     */
    public function getAllMailingLists(): array
    {
        $mailingLists = [];

        // Get sites to loop through so we can ensure that we get all sendouts
        $sites = Craft::$app->getSites()->getAllSites();

        foreach ($sites as $site) {
            $mailingLists = array_merge($mailingLists, MailingListElement::find()->site($site)->all());
        }

        return $mailingLists;
    }

    /**
     * Adds a contact interaction
     *
     * @param ContactElement $contact
     * @param MailingListElement $mailingList
     * @param string $interaction
     * @param string|null $sourceType
     * @param string|null $source
     * @param bool|null $verify
     */
    public function addContactInteraction(ContactElement $contact, MailingListElement $mailingList, string $interaction, string $sourceType = null, string $source = null, bool $verify = null)
    {
        $sourceType = $sourceType ?? '';
        $source = $source ?? '';
        $verify = $verify ?? false;

        // Ensure that interaction exists
        if (!in_array($interaction, ContactMailingListModel::INTERACTIONS, true)) {
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
        if ($contactMailingListRecord->{$interaction} === null) {
            $contactMailingListRecord->{$interaction} = new DateTime();
        }

        // If subscribing
        if ($interaction == 'subscribed') {
            // Set source and source URL if not already set
            $contactMailingListRecord->sourceType = $contactMailingListRecord->sourceType ?? $sourceType;
            $contactMailingListRecord->source = $contactMailingListRecord->source ?? $source;

            if ($verify AND $contactMailingListRecord->verified === null) {
                $contactMailingListRecord->verified = new DateTime();
            }
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
     * @throws Exception|Throwable in case delete failed.
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

    /**
     * Syncs a mailing list to a user group
     *
     * @param MailingListElement $mailingList
     */
    public function syncMailingList(MailingListElement $mailingList)
    {
        if ($mailingList->syncedUserGroupId === null) {
            return;
        }


    }
}
