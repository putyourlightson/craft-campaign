<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\services;

use Craft;
use craft\base\Component;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use DateTime;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\models\PendingContactModel;
use putyourlightson\campaign\records\PendingContactRecord;
use yii\helpers\Json;

/**
 * @since 1.10.0
 */
class PendingContactsService extends Component
{
    /**
     * Returns a pending contact by PID.
     */
    public function getPendingContactByPid(string $pid): ?PendingContactModel
    {
        /** @var PendingContactRecord|null $pendingContactRecord */
        $pendingContactRecord = PendingContactRecord::find()
            ->andWhere(['pid' => $pid])
            ->one();

        if ($pendingContactRecord === null) {
            return null;
        }

        $pendingContact = new PendingContactModel();
        $pendingContact->setAttributes($pendingContactRecord->getAttributes(), false);

        return $pendingContact;
    }

    /**
     * Returns whether a pending contact has been trashed.
     */
    public function getIsPendingContactTrashed(string $pid): bool
    {
        $count = PendingContactRecord::findTrashed()
            ->andWhere(['pid' => $pid])
            ->count();

        return $count > 0;
    }

    /**
     * Saves a pending contact.
     */
    public function savePendingContact(PendingContactModel $pendingContact): bool
    {
        $this->purgeExpiredPendingContacts();

        $settings = Campaign::$plugin->settings;

        $condition = [
            'email' => $pendingContact->email,
            'mailingListId' => $pendingContact->mailingListId,
        ];

        // Check if max pending contacts reached for this email
        $numPendingContactRecords = PendingContactRecord::find()
            ->where($condition)
            ->count();

        if ($numPendingContactRecords >= $settings->maxPendingContacts) {
            // Delete oldest pending contacts
            $pendingContactRecords = PendingContactRecord::find()
                ->where($condition)
                ->orderBy(['dateCreated' => SORT_ASC])
                ->limit($numPendingContactRecords - $settings->maxPendingContacts + 1)
                ->all();

            foreach ($pendingContactRecords as $pendingContactRecord) {
                $pendingContactRecord->delete();
            }
        }

        $pendingContactRecord = new PendingContactRecord();
        $pendingContactRecord->setAttributes($pendingContact->getAttributes(), false);

        return $pendingContactRecord->save();
    }

    /**
     * Verifies a pending contact.
     */
    public function verifyPendingContact(string $pid): ?PendingContactModel
    {
        $pendingContact = $this->getPendingContactByPid($pid);

        if ($pendingContact === null) {
            return null;
        }

        // Get contact if it exists
        $contact = Campaign::$plugin->contacts->getContactByEmail($pendingContact->email);

        if ($contact === null) {
            // Get trashed contact
            $contact = Campaign::$plugin->contacts->getContactByEmail($pendingContact->email, true);

            // If no contact found or trashed contact could not be restored
            if ($contact === null || !Craft::$app->getElements()->restoreElement($contact)) {
                $contact = new ContactElement();
            }
        }

        $contact->verified = new DateTime();

        $contact->email = $pendingContact->email;

        // Set field values
        $contact->setFieldValues(Json::decode($pendingContact->fieldData));

        if (!Craft::$app->getElements()->saveElement($contact, false)) {
            return null;
        }

        // Soft-delete pending contact
        /** @var PendingContactRecord|null $pendingContactRecord */
        $pendingContactRecord = PendingContactRecord::find()
            ->andWhere(['pid' => $pendingContact->pid])
            ->one();

        if ($pendingContactRecord !== null) {
            $pendingContactRecord->softDelete();
        }

        return $pendingContact;
    }

    /**
     * Deletes expired pending contacts
     */
    public function purgeExpiredPendingContacts(): void
    {
        $settings = Campaign::$plugin->settings;

        if ($settings->purgePendingContactsDuration === 0) {
            return;
        }

        $pastTime = DateTimeHelper::toDateInterval($settings->purgePendingContactsDuration);
        $pendingContactRecords = PendingContactRecord::find()
            ->andWhere(['<', 'dateUpdated', Db::prepareDateForDb($pastTime)])
            ->all();

        /** @var PendingContactRecord $pendingContactRecord */
        foreach ($pendingContactRecords as $pendingContactRecord) {
            $softDeleted = false;

            if ($pendingContactRecord->dateDeleted !== null) {
                $softDeleted = true;
            }

            $pendingContactRecord->delete();

            if (!$softDeleted) {
                Campaign::$plugin->log('Deleted pending contact “{email}” because they took too long to verify their email.', [
                    'email' => $pendingContactRecord->email,
                ]);
            }
        }
    }
}
