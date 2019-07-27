<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\services;

use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\models\PendingContactModel;

use Craft;
use craft\base\Component;
use craft\errors\MissingComponentException;
use yii\base\Exception;

/**
 * ContactsService
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 */
class ContactsService extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * Returns contact by ID
     *
     * @param int $contactId
     *
     * @return ContactElement|null
     */
    public function getContactById(int $contactId)
    {
        $contact = ContactElement::find()
            ->id($contactId)
            ->status(null)
            ->one();

        return $contact;
    }

    /**
     * Returns contacts by IDs
     *
     * @param int[] $contactIds
     *
     * @return ContactElement[]
     */
    public function getContactsByIds(array $contactIds): array
    {
        if (empty($contactIds)) {
            return [];
        }

        return ContactElement::find()
            ->id($contactIds)
            ->status(null)
            ->all();
    }

    /**
     * Returns contact by user ID
     *
     * @param int $userId
     *
     * @return ContactElement|null
     */
    public function getContactByUserId(int $userId)
    {
        if (!$userId) {
            return null;
        }

        $contact = ContactElement::find()
            ->userId($userId)
            ->status(null)
            ->one();

        return $contact;
    }

    /**
     * Returns contact by CID
     *
     * @param string $cid
     *
     * @return ContactElement|null
     */
    public function getContactByCid(string $cid)
    {
        if (!$cid) {
            return null;
        }

        $contact = ContactElement::find()
            ->cid($cid)
            ->status(null)
            ->one();

        return $contact;
    }

    /**
     * Returns contact by email
     *
     * @param string $email
     * @param bool|null $trashed
     *
     * @return ContactElement|null
     */
    public function getContactByEmail(string $email, $trashed = false)
    {
        if (!$email) {
            return null;
        }

        $contact = ContactElement::find()
            ->email($email)->trashed()
            ->status(null)
            ->trashed($trashed)
            ->one();

        return $contact;
    }

    /**
     * Saves a pending contact
     *
     * @param PendingContactModel $pendingContact
     *
     * @return bool
     *
     * @deprecated in 1.10.0. Use [[FormsService::savePendingContact()]] instead.
     */
    public function savePendingContact(PendingContactModel $pendingContact): bool
    {
        Craft::$app->getDeprecator()->log('ContactsService::savePendingContact()', 'The “ContactsService::savePendingContact()” method has been deprecated. Use “FormsService::savePendingContact()” instead.');

        return Campaign::$plugin->forms->savePendingContact($pendingContact);
    }

    /**
     * Sends a verification email
     *
     * @param PendingContactModel $pendingContact
     * @param MailingListElement $mailingList
     *
     * @return bool
     * @throws Exception
     * @throws MissingComponentException
     *
     * @deprecated in 1.10.0. Use [[FormsService::sendVerifySubscribeEmail()]] instead.
     */
    public function sendVerificationEmail(PendingContactModel $pendingContact, MailingListElement $mailingList): bool
    {
        Craft::$app->getDeprecator()->log('ContactsService::sendVerificationEmail()', 'The “ContactsService::sendVerificationEmail()” method has been deprecated. Use “FormsService::sendVerifySubscribeEmail()” instead.');

        return Campaign::$plugin->forms->sendVerifySubscribeEmail($pendingContact, $mailingList);
    }

    /**
     * Verifies a pending contact
     *
     * @param string $pid
     *
     * @return PendingContactModel|null
     *
     * @deprecated in 1.10.0. Use [[FormsService::verifyPendingContact()]] instead.
     */
    public function verifyPendingContact(string $pid)
    {
        Craft::$app->getDeprecator()->log('ContactsService::verifyPendingContact()', 'The “ContactsService::verifyPendingContact()” method has been deprecated. Use “FormsService::verifyPendingContact()” instead.');

        return Campaign::$plugin->forms->verifyPendingContact($pid);
    }

    /**
     * Deletes expired pending contacts
     *
     * @deprecated in 1.10.0. Use [[FormsService::purgeExpiredPendingContacts()]] instead.
     */
    public function purgeExpiredPendingContacts()
    {
        Craft::$app->getDeprecator()->log('ContactsService::purgeExpiredPendingContacts()', 'The “ContactsService::purgeExpiredPendingContacts()” method has been deprecated. Use “FormsService::purgeExpiredPendingContacts()” instead.');

        return Campaign::$plugin->forms->purgeExpiredPendingContacts();
    }
}
