<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\services;

use Craft;
use craft\base\Component;
use craft\helpers\Html;
use craft\helpers\UrlHelper;
use craft\web\View;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\events\SubscribeContactEvent;
use putyourlightson\campaign\events\UnsubscribeContactEvent;
use putyourlightson\campaign\events\UpdateContactEvent;

use putyourlightson\campaign\helpers\ContactActivityHelper;
use putyourlightson\campaign\helpers\SettingsHelper;
use putyourlightson\campaign\helpers\StringHelper;
use putyourlightson\campaign\models\PendingContactModel;
use Twig\Error\Error;
use yii\web\MethodNotAllowedHttpException;

/**
 * @since 1.10.0
 */
class FormsService extends Component
{
    /**
     * @event SubscribeContactEvent
     */
    public const EVENT_BEFORE_SUBSCRIBE_CONTACT = 'beforeSubscribeContact';

    /**
     * @event SubscribeContactEvent
     */
    public const EVENT_AFTER_SUBSCRIBE_CONTACT = 'afterSubscribeContact';

    /**
     * @event UnsubscribeContactEvent
     */
    public const EVENT_BEFORE_UNSUBSCRIBE_CONTACT = 'beforeUnsubscribeContact';

    /**
     * @event UnsubscribeContactEvent
     */
    public const EVENT_AFTER_UNSUBSCRIBE_CONTACT = 'afterUnsubscribeContact';

    /**
     * @event UpdateContactEvent
     */
    public const EVENT_BEFORE_UPDATE_CONTACT = 'beforeUpdateContact';

    /**
     * @event UpdateContactEvent
     */
    public const EVENT_AFTER_UPDATE_CONTACT = 'afterUpdateContact';

    /**
     * Sends a verify subscribe email.
     */
    public function sendVerifySubscribeEmail(PendingContactModel $pendingContact, MailingListElement $mailingList): bool
    {
        // Set the current site from the mailing list's site ID
        Craft::$app->getSites()->setCurrentSite($mailingList->siteId);

        $path = Craft::$app->getConfig()->getGeneral()->actionTrigger . '/campaign/forms/verify-subscribe';
        $url = UrlHelper::siteUrl($path, [
            'pid' => $pendingContact->pid,
            'mlid' => $mailingList->id,
        ]);

        $subject = Craft::t('campaign', 'Verify your email address');
        $bodyText = Craft::t('campaign', 'Thank you for subscribing to the mailing list. Please verify your email address by clicking on the following link:');
        $htmlBody = $bodyText . '<br>' . Html::a($url, $url);
        $plaintextBody = $bodyText . PHP_EOL . $url;

        // Get subject from setting if defined
        $subject = $mailingList->mailingListType->subscribeVerificationEmailSubject ?: $subject;

        // Get body from template if defined
        if ($mailingList->mailingListType->subscribeVerificationEmailTemplate) {
            try {
                $htmlBody = Craft::$app->getView()->renderTemplate(
                    $mailingList->mailingListType->subscribeVerificationEmailTemplate,
                    [
                        'message' => $bodyText,
                        'url' => $url,
                        'mailingList' => $mailingList,
                        'pendingContact' => $pendingContact,
                    ],
                    View::TEMPLATE_MODE_SITE,
                );
                $plaintextBody = StringHelper::htmlToPlaintext($htmlBody);
            }
            catch (Error) {
            }
        }

        return $this->_sendEmail($pendingContact->email, $subject, $htmlBody, $plaintextBody, $mailingList->siteId);
    }

    /**
     * Sends a verify unsubscribe email.
     */
    public function sendVerifyUnsubscribeEmail(ContactElement $contact, MailingListElement $mailingList): bool
    {
        // Set the current site from the mailing list's site ID
        Craft::$app->getSites()->setCurrentSite($mailingList->siteId);

        $path = Craft::$app->getConfig()->getGeneral()->actionTrigger . '/campaign/forms/verify-unsubscribe';
        $url = UrlHelper::siteUrl($path, [
            'cid' => $contact->cid,
            'uid' => $contact->uid,
            'mlid' => $mailingList->id,
        ]);

        $subject = Craft::t('campaign', 'Verify unsubscribe');
        $bodyText = Craft::t('campaign', 'Please verify that you would like to unsubscribe from the mailing list by clicking on the following link:');
        $htmlBody = $bodyText . '<br>' . Html::a($url, $url);
        $plaintextBody = $bodyText . PHP_EOL . $url;

        // Get subject from setting if defined
        $subject = $mailingList->mailingListType->unsubscribeVerificationEmailSubject ?: $subject;

        // Get body from template if defined
        if ($mailingList->mailingListType->unsubscribeVerificationEmailTemplate) {
            try {
                $htmlBody = Craft::$app->getView()->renderTemplate(
                    $mailingList->mailingListType->unsubscribeVerificationEmailTemplate,
                    [
                        'message' => $bodyText,
                        'url' => $url,
                        'mailingList' => $mailingList,
                        'contact' => $contact,
                    ],
                    View::TEMPLATE_MODE_SITE,
                );
            }
            catch (Error) {
            }
        }

        return $this->_sendEmail($contact->email, $subject, $htmlBody, $plaintextBody, $mailingList->siteId);
    }

    /**
     * Creates and subscribes a contact, with verification if enabled on the mailing list.
     *
     * @since 2.1.0
     */
    public function createAndSubscribeContact(string $email, MailingListElement $mailingList, string $sourceType = null, string $source = null): ContactElement|PendingContactModel
    {
        // Get contact if it exists
        $contact = Campaign::$plugin->contacts->getContactByEmail($email);

        if ($contact === null) {
            $contact = new ContactElement();
            $contact->email = $email;
        }

        // Disallow if blocked
        if ($contact->blocked !== null) {
            throw new MethodNotAllowedHttpException(Craft::t('campaign', 'This email address is blocked from subscribing.'));
        }

        // Set field values
        $contact->setFieldValuesFromRequest('fields');

        // If subscribe verification required
        if ($mailingList->getMailingListType()->subscribeVerificationRequired) {
            // Mock before save so we can validate the contact
            $contact->beforeSave(true);

            // Validate the contact
            if (!$contact->validate()) {
                return $contact;
            }

            // Create pending contact
            $pendingContact = new PendingContactModel();
            $pendingContact->email = $email;
            $pendingContact->mailingListId = $mailingList->id;
            $pendingContact->source = $source;
            $pendingContact->fieldData = $contact->getSerializedFieldValues();

            // Save pending contact
            if (!Campaign::$plugin->pendingContacts->savePendingContact($pendingContact)) {
                return $pendingContact;
            }

            // Send verification email
            Campaign::$plugin->forms->sendVerifySubscribeEmail($pendingContact, $mailingList);
        }
        else {
            // Save contact
            if (!Craft::$app->getElements()->saveElement($contact)) {
                return $contact;
            }

            $this->subscribeContact($contact, $mailingList, $sourceType, $source);
        }

        return $contact;
    }

    /**
     * Subscribes a contact.
     */
    public function subscribeContact(ContactElement $contact, MailingListElement $mailingList, string $sourceType = null, string $source = null, bool $verify = null): void
    {
        $sourceType = $sourceType ?? '';
        $source = $source ?? '';
        $verify = $verify ?? false;

        // Fire a before event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SUBSCRIBE_CONTACT)) {
            $this->trigger(self::EVENT_BEFORE_SUBSCRIBE_CONTACT, new SubscribeContactEvent([
                'contact' => $contact,
                'mailingList' => $mailingList,
                'sourceType' => $sourceType,
                'source' => $source,
            ]));
        }

        Campaign::$plugin->mailingLists->addContactInteraction($contact, $mailingList, 'subscribed', $sourceType, $source, $verify);

        // Update contact activity
        ContactActivityHelper::updateContactActivity($contact);

        // Fire an after event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SUBSCRIBE_CONTACT)) {
            $this->trigger(self::EVENT_AFTER_SUBSCRIBE_CONTACT, new SubscribeContactEvent([
                'contact' => $contact,
                'mailingList' => $mailingList,
                'sourceType' => $sourceType,
                'source' => $source,
            ]));
        }
    }

    /**
     * Unsubscribes a contact.
     */
    public function unsubscribeContact(ContactElement $contact, MailingListElement $mailingList): void
    {
        // Fire a before event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_UNSUBSCRIBE_CONTACT)) {
            $this->trigger(self::EVENT_BEFORE_UNSUBSCRIBE_CONTACT, new UnsubscribeContactEvent([
                'contact' => $contact,
                'mailingList' => $mailingList,
            ]));
        }

        Campaign::$plugin->mailingLists->addContactInteraction($contact, $mailingList, 'unsubscribed');

        // Fire an after event
        if ($this->hasEventHandlers(self::EVENT_AFTER_UNSUBSCRIBE_CONTACT)) {
            $this->trigger(self::EVENT_AFTER_UNSUBSCRIBE_CONTACT, new UnsubscribeContactEvent([
                'contact' => $contact,
                'mailingList' => $mailingList,
            ]));
        }

        // Update contact activity
        ContactActivityHelper::updateContactActivity($contact);
    }

    /**
     * Updates a contact.
     */
    public function updateContact(ContactElement $contact): bool
    {
        // Fire a before event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_UPDATE_CONTACT)) {
            $this->trigger(self::EVENT_BEFORE_UPDATE_CONTACT, new UpdateContactEvent([
                'contact' => $contact,
            ]));
        }

        if (!Craft::$app->getElements()->saveElement($contact)) {
            return false;
        }

        // Update contact activity
        ContactActivityHelper::updateContactActivity($contact);

        // Fire an after event
        if ($this->hasEventHandlers(self::EVENT_AFTER_UPDATE_CONTACT)) {
            $this->trigger(self::EVENT_AFTER_UPDATE_CONTACT, new UpdateContactEvent([
                'contact' => $contact,
            ]));
        }

        return true;
    }

    /**
     * Sends an email to a contact.
     */
    public function _sendEmail(string $email, string $subject, string $htmlBody, string $plaintextBody, int $siteId): bool
    {
        // Get from name and email
        $fromNameEmail = SettingsHelper::getFromNameEmail($siteId);

        // Create message using the mailer for verification emails
        $mailer = SettingsHelper::getMailerForVerificationEmails();
        $message = $mailer->compose()
            ->setFrom([$fromNameEmail['email'] => $fromNameEmail['name']])
            ->setTo($email)
            ->setSubject($subject)
            ->setHtmlBody($htmlBody)
            ->setTextBody($plaintextBody);

        if ($fromNameEmail['replyTo']) {
            $message->setReplyTo($fromNameEmail['replyTo']);
        }

        return $message->send();
    }
}
