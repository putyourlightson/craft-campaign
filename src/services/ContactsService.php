<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\services;

use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\MailingListElement;

use Craft;
use craft\base\Component;
use craft\errors\MissingComponentException;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\UrlHelper;
use craft\mail\Message;
use putyourlightson\campaign\helpers\StringHelper;
use putyourlightson\campaign\models\PendingContactModel;
use putyourlightson\campaign\records\PendingContactRecord;
use yii\base\Exception;
use yii\db\StaleObjectException;


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
            ->where(['cid' => $cid])
            ->status(null)
            ->one();

        return $contact;
    }

    /**
     * Returns contact by email
     *
     * @param string $email
     *
     * @return ContactElement|null
     */
    public function getContactByEmail(string $email)
    {
        if (!$email) {
            return null;
        }

        $contact = ContactElement::find()
            ->email($email)
            ->status(null)
            ->one();

        return $contact;
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
        // Get pending contact if it exists
        $pendingContactRecord = PendingContactRecord::find()
            ->where([
                'email' => $pendingContact->email,
                'mailingListId' => $pendingContact->mailingListId,
            ])
            ->one();

        if ($pendingContactRecord === null) {
            $pendingContactRecord = new PendingContactRecord();
        }

        $pendingContactRecord->pid = $pendingContact->pid;
        $pendingContactRecord->email = $pendingContact->email;
        $pendingContactRecord->mailingListId = $pendingContact->mailingListId;
        $pendingContactRecord->sourceUrl = $pendingContact->sourceUrl;
        $pendingContactRecord->fieldData = $pendingContact->fieldData;

        $pendingContactRecord->save();

        return true;
    }

    /**
     * Sends a verification email
     *
     * @param PendingContactModel $pendingContact
     *
     * @return bool
     * @throws Exception
     * @throws MissingComponentException
     */
    public function sendVerificationEmail(PendingContactModel $pendingContact): bool
    {
        $path = Craft::$app->getConfig()->getGeneral()->actionTrigger.'/campaign/t/verify-email';
        $url = UrlHelper::siteUrl($path, ['pid' => $pendingContact->pid]);

        $mailer = Campaign::$plugin->createMailer();

        $settings = Campaign::$plugin->getSettings();

        $subject = Craft::t('campaign', 'Verify your email address');
        $body = Craft::t('campaign', 'Thank you for subscribing to the mailing list. Please verify your email address by clicking on this link:')."\n".$url;

        // Create message
        /** @var Message $message */
        $message = $mailer->compose()
            ->setFrom([$settings->defaultFromEmail => $settings->defaultFromName])
            ->setTo($pendingContact->email)
            ->setSubject($subject)
            ->setHtmlBody($body)
            ->setTextBody($body);

        return $message->send();
    }

    /**
     * Verifies a pending contact
     *
     * @param string $pid
     *
     * @return PendingContactModel|null
     * @throws StaleObjectException
     * @throws \Throwable
     */
    public function verifyPendingContact(string $pid)
    {
        // Get pending contact
        $pendingContactRecord = PendingContactRecord::find()
            ->where(['pid' => $pid])
            ->one();

        if ($pendingContactRecord === null) {
            return null;
        }

        /** @var PendingContactModel $pendingContact */
        $pendingContact = PendingContactModel::populateModel($pendingContactRecord);

        // Get contact if it exists
        $contact = $this->getContactByEmail($pendingContact->email);

        if ($contact === null) {
            $contact = new ContactElement();
        }

        // Set field values
        $contact->email = $pendingContact->email;
        $contact->fieldLayoutId = Campaign::$plugin->getSettings()->contactFieldLayoutId;
        $contact->setFieldValues($pendingContact->fieldData);

        $contact->save();

        // Delete pending contact
        $pendingContactRecord = PendingContactRecord::find()
            ->where(['pid' => $pendingContact->pid])
            ->one();

        if ($pendingContactRecord !== null) {
            $pendingContactRecord->delete();
        }

        return $pendingContact;
    }

    /**
     * Deletes expired pending contacts
     *
     * @throws \Throwable
     */
    public function purgeExpiredPendingContacts()
    {
        $settings = Campaign::$plugin->getSettings();

        if ($settings->purgePendingContactsDuration === 0) {
            return;
        }

        $interval = DateTimeHelper::secondsToInterval($settings->purgePendingContactsDuration);
        $expire = DateTimeHelper::currentUTCDateTime();
        $pastTime = $expire->sub($interval);

        $pendingContactRecords = PendingContactRecord::find()
            ->where(['<', 'dateUpdated', Db::prepareDateForDb($pastTime)])
            ->all();

        foreach ($pendingContactRecords as $pendingContactRecord) {
            $pendingContactRecord->delete();

            /** @var PendingContactRecord $pendingContactRecord */
            Craft::info("Deleted pending contact {$pendingContactRecord->email}, because they took too long to verify their email.", __METHOD__);
        }
    }
}