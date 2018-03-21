<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\services;

use craft\helpers\ConfigHelper;
use craft\helpers\DateTimeHelper;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\MailingListElement;

use Craft;
use craft\base\Component;
use craft\errors\MissingComponentException;
use craft\helpers\UrlHelper;
use craft\mail\Message;
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
     * Sends a verification email
     *
     * @param ContactElement $contact
     * @param MailingListElement $mailingList
     * @param string|null $referrer
     *
     * @return bool
     * @throws MissingComponentException
     * @throws Exception
     */
    public function sendVerificationEmail(ContactElement $contact, MailingListElement $mailingList, $referrer = ''): bool
    {
        $path = Craft::$app->getConfig()->getGeneral()->actionTrigger.'/campaign/t/verify-email';
        $url = UrlHelper::siteUrl($path, ['cid' => $contact->cid, 'mlid' => $mailingList->mlid, 'referrer' => $referrer]);

        $mailer = Campaign::$plugin->createMailer();

        $settings = Campaign::$plugin->getSettings();

        $subject = Craft::t('campaign', 'Verify your email address');
        $body = Craft::t('campaign', "Thank you for subscribing to the mailing list. Please verify your email address by clicking on this link: \n{link}", ['link' => $url]);

        // Create message
        /** @var Message $message */
        $message = $mailer->compose()
            ->setFrom([$settings->defaultFromEmail => $settings->defaultFromName])
            ->setTo($contact->email)
            ->setSubject($subject)
            ->setHtmlBody($body)
            ->setTextBody($body);

        return $message->send();
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

        $interval = DateTimeHelper::secondsToInterval($settings->purgePendingContactsDuration);
        $expire = DateTimeHelper::currentUTCDateTime();
        $pastTime = $expire->sub($interval);

        $contactIds = ContactElement::find()
            ->where([
                'and',
                ['pending' => true],
                ['<', 'dateCreated', Db::prepareDateForDb($pastTime)]
            ])
            ->ids();

        $elementsService = Craft::$app->getElements();

        foreach ($contactIds as $contactId) {
            $contact = $this->getContactById($contactId);
            $elementsService->deleteElement($contact);
            Craft::info("Deleted pending contact {$contact->email} ({$contactId}), because they took too long to verify their email.", __METHOD__);
        }
    }
}