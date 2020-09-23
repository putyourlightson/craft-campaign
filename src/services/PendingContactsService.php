<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\services;

use craft\helpers\ConfigHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use DateTime;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\models\PendingContactModel;

use Craft;
use craft\base\Component;
use putyourlightson\campaign\records\PendingContactRecord;
use yii\helpers\Json;

/**
 * PendingContactsService
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.10.0
 */
class PendingContactsService extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * Returns pending contact by PID
     *
     * @param string $pid
     *
     * @return PendingContactModel|null
     */
    public function getPendingContactByPid(string $pid)
    {
        // Get pending contact
        $pendingContactRecord = PendingContactRecord::find()
            ->andWhere(['pid' => $pid])
            ->one();

        if ($pendingContactRecord === null) {
            return null;
        }

        /** @var PendingContactModel $pendingContact */
        $pendingContact = PendingContactModel::populateModel($pendingContactRecord, false);

        return $pendingContact;
    }

    /**
     * Returns whether a pending contact has been trashed
     *
     * @param string $pid
     *
     * @return bool
     */
    public function getIsPendingContactTrashed(string $pid): bool
    {
        $count = PendingContactRecord::findTrashed()
            ->andWhere(['pid' => $pid])
            ->count();

        return $count > 0;
    }

    /**
     * Saves a pending contact
     *
     * @param PendingContactModel $pendingContact
     *
     * @return bool
     */
    public function savePendingContact(PendingContactModel $pendingContact): bool
    {
        $this->purgeExpiredPendingContacts();

        $settings = Campaign::$plugin->getSettings();

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
     * Verifies a pending contact
     *
     * @param string $pid
     *
     * @return PendingContactModel|null
     */
    public function verifyPendingContact(string $pid)
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

        if (!Craft::$app->getElements()->saveElement($contact)) {
            return null;
        }

        // Soft-delete pending contact
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
    public function purgeExpiredPendingContacts()
    {
        $settings = Campaign::$plugin->getSettings();

        if ($settings->purgePendingContactsDuration === 0) {
            return;
        }

        $purgePendingContactsDuration = ConfigHelper::durationInSeconds($settings->purgePendingContactsDuration);
        $interval = DateTimeHelper::secondsToInterval($purgePendingContactsDuration);
        $expire = DateTimeHelper::currentUTCDateTime();
        $pastTime = $expire->sub($interval);

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
                Campaign::$plugin->log('Deleted pending contact "{email}" because they took too long to verify their email.', ['email' => $pendingContactRecord->email]);
            }
        }
    }
}
