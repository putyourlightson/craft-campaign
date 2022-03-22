<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\services;

use craft\base\Component;

use putyourlightson\campaign\elements\ContactElement;

class ContactsService extends Component
{
    /**
     * Returns a contact by ID.
     */
    public function getContactById(int $contactId): ?ContactElement
    {
        return ContactElement::find()
            ->id($contactId)
            ->status(null)
            ->one();
    }

    /**
     * Returns an array of contacts by IDs
     *
     * @param int[] $contactIds
     * @return ContactElement[]
     */
    public function getContactsByIds(?array $contactIds): array
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
     * Returns contact by user ID.
     */
    public function getContactByUserId(int $userId): ?ContactElement
    {
        if (!$userId) {
            return null;
        }

        return ContactElement::find()
            ->userId($userId)
            ->status(null)
            ->one();
    }

    /**
     * Returns a contact by CID.
     */
    public function getContactByCid(string $cid): ?ContactElement
    {
        if (!$cid) {
            return null;
        }

        return ContactElement::find()
            ->cid($cid)
            ->status(null)
            ->one();
    }

    /**
     * Returns a contact by email.
     */
    public function getContactByEmail(string $email, bool $trashed = false): ?ContactElement
    {
        if (!$email) {
            return null;
        }

        return ContactElement::find()
            ->email($email)
            ->status(null)
            ->trashed($trashed)
            ->one();
    }
}
