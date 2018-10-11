<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\services;

use craft\records\Element_SiteSettings;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\controllers\WebhookController;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\SendoutElement;
use putyourlightson\campaign\events\SendoutEmailEvent;
use putyourlightson\campaign\jobs\SendoutJob;
use putyourlightson\campaign\records\ContactCampaignRecord;
use putyourlightson\campaign\records\LinkRecord;

use Craft;
use craft\base\Component;
use craft\errors\ElementNotFoundException;
use craft\errors\MissingComponentException;
use craft\helpers\Db;
use craft\helpers\UrlHelper;
use craft\mail\Mailer;
use craft\mail\Message;
use putyourlightson\campaign\records\SendoutRecord;
use yii\base\Exception;
use yii\base\InvalidConfigException;

/**
 * SendoutsService
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 *
 * @property Mailer $mailer
 */
class SendoutsService extends Component
{
    // Constants
    // =========================================================================

    /**
     * @event SendoutEvent
     */
    const EVENT_BEFORE_SEND = 'beforeSend';

    /**
     * @event SendoutEvent
     */
    const EVENT_AFTER_SEND = 'afterSend';

    /**
     * @event SendoutEmailEvent
     */
    const EVENT_BEFORE_SEND_EMAIL = 'beforeSendEmail';

    /**
     * @event SendoutEmailEvent
     */
    const EVENT_AFTER_SEND_EMAIL = 'afterSendEmail';

    // Properties
    // =========================================================================

    /**
     * @var Mailer
     */
    private $_mailer;

    /**
     * @var array
     */
    private $_links = [];

    // Public Methods
    // =========================================================================

    /**
     * Returns sendout by ID
     *
     * @param int $sendoutId
     *
     * @return SendoutElement|null
     */
    public function getSendoutById(int $sendoutId)
    {
        // Get site ID from element site settings
        $siteId = Element_SiteSettings::find()
            ->select('siteId')
            ->where(['elementId' => $sendoutId])
            ->scalar();

        if ($siteId === null) {
            return null;
        }

        $sendout = SendoutElement::find()
            ->id($sendoutId)
            ->siteId($siteId)
            ->status(null)
            ->one();

        return $sendout;
    }

    /**
     * Returns sendout by SID
     *
     * @param string $sid
     *
     * @return SendoutElement|null
     */
    public function getSendoutBySid(string $sid)
    {
        if (!$sid) {
            return null;
        }

        $sendout = SendoutElement::find()
            ->where(['sid' => $sid])
            ->status(null)
            ->one();

        return $sendout;
    }

    /**
     * Returns mailer or creates one if it does not yet exist
     *
     * @return Mailer
     * @throws MissingComponentException
     * @throws InvalidConfigException
     */
    public function getMailer(): Mailer
    {
        if ($this->_mailer !== null) {
            return $this->_mailer;
        }
        
        $this->_mailer = Campaign::$plugin->createMailer();

        return $this->_mailer;
    }

    /**
     * Queues pending sendouts
     *
     * @return int
     * @throws \Throwable
     */
    public function queuePendingSendouts(): int
    {
        $count = 0;
        $now = new \DateTime();

        // Find pending sendouts whose send date is in the past
        $sendouts = SendoutElement::find()
            ->status(SendoutElement::STATUS_PENDING)
            ->where(Db::parseDateParam('sendDate', $now, '<='))
            ->all();

        /** @var SendoutElement[] $sendouts */
        foreach ($sendouts as $sendout) {
            // Queue regular and scheduled sendouts, automated and recurring sendouts if pro version and the sendout can send now and there are pending recipients
            if ($sendout->sendoutType == 'regular' OR $sendout->sendoutType == 'scheduled' OR (($sendout->sendoutType == 'automated' OR $sendout->sendoutType == 'recurring') AND Campaign::$plugin->getIsPro() AND $sendout->getCanSendNow() AND $sendout->getHasPendingRecipients())) {
                // Add sendout job to queue
                Craft::$app->getQueue()->push(new SendoutJob([
                    'sendoutId' => $sendout->id,
                    'title' => $sendout->title,
                ]));

                $sendout->sendStatus = SendoutElement::STATUS_QUEUED;

                $this->_updateSendoutRecord($sendout, ['sendStatus']);

                $count++;
            }
        }

        return $count;
    }

    /**
     * Sends a test
     *
     * @param SendoutElement $sendout
     * @param ContactElement $contact
     *
     * @return bool Whether the test was sent successfully
     * @throws Exception
     * @throws MissingComponentException
     * @throws InvalidConfigException
     */
    public function sendTest(SendoutElement $sendout, ContactElement $contact): bool
    {
        // Get campaign
        $campaign = $sendout->getCampaign();

        if ($campaign === null) {
            return false;
        }

        // Get body
        $htmlBody = $campaign->getHtmlBody($contact, $sendout);
        $plaintextBody = $campaign->getPlaintextBody($contact, $sendout);

        // Convert links in HTML body
        $htmlBody = $this->_convertLinks($htmlBody, $contact, $sendout);

        // Get mailer
        $mailer = $this->getMailer();

        // Compose message
        /** @var Message $message*/
        $message = $mailer->compose()
            ->setFrom([$sendout->fromEmail => $sendout->fromName])
            ->setTo($contact->email)
            ->setSubject('[Test] '.$sendout->subject)
            ->setHtmlBody($htmlBody)
            ->setTextBody($plaintextBody);

        // Add webhooks to message
        $this->_addWebhooks($message, $sendout->sid);

        return $message->send();
    }

    /**
     * Sends an email
     *
     * @param SendoutElement $sendout
     * @param ContactElement $contact
     * @param int $mailingListId
     *
     * @throws \Throwable
     * @throws Exception
     */
    public function sendEmail(SendoutElement $sendout, ContactElement $contact, int $mailingListId)
    {
        if ($sendout->getIsSendable() === false) {
            return;
        }

        // Return if contact has complained or bounced
        if ($contact->complained !== null OR $contact->bounced !== null) {
            return;
        }

        // Get campaign
        $campaign = $sendout->getCampaign();

        if ($campaign === null) {
            return;
        }

        /** @var ContactCampaignRecord|null $contactCampaignRecord */
        $contactCampaignRecord = ContactCampaignRecord::find()
            ->where([
                'contactId' => $contact->id,
                'sendoutId' => $sendout->id,
            ])
            ->one();

        if ($contactCampaignRecord === null) {
            $contactCampaignRecord = new ContactCampaignRecord();
            $contactCampaignRecord->contactId = $contact->id;
            $contactCampaignRecord->sendoutId = $sendout->id;
        }
        else if ($contactCampaignRecord->sent !== null) {
            // Ensure this is a recurring sendout that can be sent to contacts multiple times
            if (!($sendout->sendoutType == 'recurring' AND $sendout->schedule->canSendToContactsMultipleTimes)) {
                return;
            }

            $now = new \DateTime();

            // Ensure not already sent today
            if ($contactCampaignRecord->sent !== null AND $contactCampaignRecord->sent > $now->format('Y-m-d')) {
                return;
            }
        }

        $contactCampaignRecord->campaignId = $campaign->id;
        $contactCampaignRecord->mailingListId = $mailingListId;
        $contactCampaignRecord->save();

        // Get subject
        $subject = Craft::$app->getView()->renderString($sendout->subject, ['contact' => $contact]);

        // Get body
        $htmlBody = $campaign->getHtmlBody($contact, $sendout);
        $plaintextBody = $campaign->getPlaintextBody($contact, $sendout);

        // Convert links in HTML body
        $htmlBody = $this->_convertLinks($htmlBody, $contact, $sendout);

        // Add secret image to HTML body
        $path = Craft::$app->getConfig()->getGeneral()->actionTrigger.'/campaign/t/open';
        $secretImageUrl = UrlHelper::siteUrl($path, ['cid' => $contact->cid, 'sid' => $sendout->sid]);
        $htmlBody .= '<img src="'.$secretImageUrl.'" width="1" height="1" />';

        // Get mailer
        $mailer = $this->getMailer();

        // If test mode is enabled then use file transport instead of sending emails
        if (Campaign::$plugin->getSettings()->testMode) {
            $mailer->useFileTransport = true;
        }

        // Create message
        /** @var Message $message */
        $message = $mailer->compose()
            ->setFrom([$sendout->fromEmail => $sendout->fromName])
            ->setTo($contact->email)
            ->setSubject($subject)
            ->setHtmlBody($htmlBody)
            ->setTextBody($plaintextBody);

        // Add webhooks to message
        $this->_addWebhooks($message, $sendout->sid);

        // Fire a before event
        $event = new SendoutEmailEvent([
            'sendout' => $sendout,
            'contact' => $contact,
            'message' => $message,
        ]);
        $this->trigger(self::EVENT_BEFORE_SEND_EMAIL, $event);

        if (!$event->isValid) {
            return;
        }

        // Send message
        $success = $message->send();

        if ($success) {
            // Update sent date
            $contactCampaignRecord->sent = new \DateTime();

            // Update recipients and last sent
            $sendout->recipients++;
            $sendout->lastSent = new \DateTime();

            $this->_updateSendoutRecord($sendout, ['recipients', 'lastSent']);
        }
        else {
            // Update failed date
            $contactCampaignRecord->failed = new \DateTime();

            // Update failed recipients and send status
            $sendout->failedRecipients++;
            $sendout->sendStatus = 'failed';
            $sendout->sendStatusMessage = Craft::t('campaign', 'Sending to {email} failed. Please check your email settings.', ['email' => $contact->email]);

            $this->_updateSendoutRecord($sendout, ['recipients', 'sendStatus', 'sendStatusMessage']);
        }

        $contactCampaignRecord->save();

        // Fire an after event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SEND_EMAIL)) {
            $this->trigger(self::EVENT_AFTER_SEND_EMAIL, new SendoutEmailEvent([
                'sendout' => $sendout,
                'contact' => $contact,
                'message' => $message,
                'success' => $success,
            ]));
        }
    }

    /**
     * Sends a notification
     *
     * @param SendoutElement $sendout
     *
     * @throws MissingComponentException
     * @throws InvalidConfigException
     */
    public function sendNotification(SendoutElement $sendout)
    {
        if (!$sendout->notificationEmailAddress) {
            return;
        }

        if ($sendout->sendStatus != 'sent' AND $sendout->sendStatus != 'failed') {
            return;
        }

        $variables = [
            'title' => $sendout->title,
            'emailSettingsUrl' => UrlHelper::cpUrl('campaign/settings/email'),
            'sendoutUrl' => $sendout->cpEditUrl,
        ];

        if ($sendout->sendStatus == 'sent') {
            $subject = Craft::t('campaign', 'Sending completed: {title}', $variables);
            $htmlBody = Craft::t('campaign', 'Sending of the sendout "<a href="{sendoutUrl}">{title}</a>" has been successfully completed!!', $variables);
            $plaintextBody = Craft::t('campaign', 'Sending of the sendout "{title}" [{sendoutUrl}] has been successfully completed!!', $variables);
        }

        else {
            $subject = Craft::t('campaign', 'Sending failed: {title}', $variables);
            $htmlBody = Craft::t('campaign', 'Sending of the sendout "<a href="{sendoutUrl}">{title}</a>" has failed. Please check that your <a href="{emailSettingsUrl}">Craft Campaign email settings</a> are correctly configured.', $variables);
            $plaintextBody = Craft::t('campaign', 'Sending of the sendout "{title}" [{sendoutUrl}] has failed. Please check that your Craft Campaign email settings [{emailSettingsUrl}] are correctly configured.', $variables);
        }

        // Get mailer
        $mailer = $this->getMailer();

        // Compose message
        /** @var Message $message*/
        $message = $mailer->compose()
            ->setFrom([$sendout->fromEmail => $sendout->fromName])
            ->setTo($sendout->notificationEmailAddress)
            ->setSubject($subject)
            ->setHtmlBody($htmlBody)
            ->setTextBody($plaintextBody);

        $message->send();
    }

    /**
     * Prepare sending
     *
     * @param SendoutElement $sendout
     *
     * @throws \Throwable
     */
    public function prepareSending(SendoutElement $sendout)
    {
        if ($sendout->sendStatus !== SendoutElement::STATUS_SENDING) {
            $sendout->sendStatus = SendoutElement::STATUS_SENDING;

            $this->_updateSendoutRecord($sendout, ['sendStatus']);
        }
    }

    /**
     * Finalise sending
     *
     * @param SendoutElement $sendout
     *
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws MissingComponentException
     * @throws \Throwable
     */
    public function finaliseSending(SendoutElement $sendout)
    {
        // Reset send status
        $sendout->sendStatus = SendoutElement::STATUS_PENDING;

        // Update send status if not automated or recurring and fully complete
        if ($sendout->sendoutType != 'automated' AND $sendout->sendoutType != 'recurring' AND !$sendout->getHasPendingRecipients()) {
            $sendout->sendStatus = SendoutElement::STATUS_SENT;
        }

        // Get campaign
        $campaign = $sendout->getCampaign();

        if ($campaign !== null) {
            // Update HTML and plaintext body
            $contact = new ContactElement();
            $sendout->htmlBody = $campaign->getHtmlBody($contact, $sendout);
            $sendout->plaintextBody = $campaign->getPlaintextBody($contact, $sendout);
        }

        $this->_updateSendoutRecord($sendout, ['sendStatus', 'htmlBody', 'plaintextBody']);

        // Update campaign recipients
        $recipients = ContactCampaignRecord::find()
            ->where(['campaignId' => $campaign->id])
            ->count();

        $campaign->recipients = $recipients;

        Craft::$app->getElements()->saveElement($campaign);

        // Send notification email
        $this->sendNotification($sendout);
    }

    /**
     * Pauses a sendout
     *
     * @param SendoutElement $sendout
     *
     * @return bool Whether the action was successful
     * @throws \Throwable
     */
    public function pauseSendout(SendoutElement $sendout): bool
    {
        if (!$sendout->getIsPausable()) {
            return false;
        }

        $sendout->sendStatus = SendoutElement::STATUS_PAUSED;

        return $this->_updateSendoutRecord($sendout, ['sendStatus']);
    }

    /**
     * Cancels a sendout
     *
     * @param SendoutElement $sendout
     *
     * @return bool Whether the action was successful
     * @throws \Throwable
     */
    public function cancelSendout(SendoutElement $sendout): bool
    {
        if (!$sendout->getIsCancellable()) {
            return false;
        }

        $sendout->sendStatus = SendoutElement::STATUS_CANCELLED;

        return $this->_updateSendoutRecord($sendout, ['sendStatus']);
    }

    /**
     * Deletes a sendout
     *
     * @param SendoutElement $sendout
     *
     * @return bool Whether the action was successful
     * @throws \Throwable
     */
    public function deleteSendout(SendoutElement $sendout): bool
    {
        if (!$sendout->getIsDeletable()) {
            return false;
        }

        return Craft::$app->getElements()->deleteElement($sendout);
    }

    // Private Methods
    // =========================================================================

    /**
     * Updates a sendout's record with the provided fields
     *
     * @param SendoutElement $sendout
     * @param array $fields
     *
     * @return bool
     */
    private function _updateSendoutRecord(SendoutElement $sendout, array $fields): bool
    {
        /** @var SendoutRecord|null $sendoutRecord */
        $sendoutRecord = SendoutRecord::find()->where(['id' => $sendout->id])->one();

        if ($sendoutRecord === null) {
            return false;
        }

        // Set attributes from sendout's fields
        $sendoutRecord->setAttributes($sendout->toArray($fields), false);

        return $sendoutRecord->save();
    }

    /**
     * Add webhooks to message
     *
     * @param Message $message
     * @param string $sid
     *
     * @throws MissingComponentException
     * @throws InvalidConfigException
     */
    private function _addWebhooks(Message $message, string $sid)
    {
        // Add SID to message header for webhooks
        $message->addHeader(WebhookController::HEADER_NAME, $sid);

        // Get mailer transport
        $mailer = $this->getMailer();
        $transport = $mailer->getTransport();

        // Add SID for custom transports
        if ($transport instanceof \Swift_Transport) {
            $transportClass = \get_class($transport);
            switch ($transportClass) {
                case 'MailgunTransport':
                    $message->addHeader('X-Mailgun-Variables', '{"'.WebhookController::HEADER_NAME.'": "'.$sid.'"}');
                    break;

                case 'MandrillTransport':
                    $message->addHeader('X-MC-Metadata', '{"'.WebhookController::HEADER_NAME.'": "'.$sid.'"}');
                    break;

                case 'SendgridTransport':
                    $message->addHeader('X-SMTPAPI', '{"unique_args": {"'.WebhookController::HEADER_NAME.'": "'.$sid.'"}}');
                    break;
            }
        }
    }

    /**
     * Converts links
     *
     * @param string $body
     * @param ContactElement $contact
     * @param SendoutElement $sendout
     *
     * @return string
     * @throws Exception
     */
    private function _convertLinks(string $body, ContactElement $contact, SendoutElement $sendout): string
    {
        // Get base URL
        $path = Craft::$app->getConfig()->getGeneral()->actionTrigger.'/campaign/t/click';
        $baseUrl = UrlHelper::siteUrl($path, ['cid' => $contact->cid, 'sid' => $sendout->sid, 'lid' => '']);

        // Use DOMDocument to parse links
        $dom = new \DOMDocument();

        // Suppress markup errors and prepend XML tag to force utf-8 encoding (https://gist.github.com/Xeoncross/9401853)
        @$dom->loadHTML('<?xml encoding="utf-8"?>'.$body);

        /** @var \DOMElement[] $elements*/
        $elements = $dom->getElementsByTagName('a');

        foreach ($elements as $element) {
            $url = $element->getAttribute('href');
            $title = $element->getAttribute('title');

            // If URL begins with http
            if (strpos($url, 'http') === 0) {
                // Ignore if unsubscribe link
                if (preg_match('/\/campaign\/(t|tracker)\/unsubscribe/i', $url)) {
                    continue;
                }

                $key = $url.':'.$title;

                // If link has not yet been converted
                if (!isset($this->_links[$key])) {
                    // Check for link record in database
                    $linkRecord = LinkRecord::findOne([
                        'campaignId' => $sendout->campaignId,
                        'url' => $url,
                        'title' => $title,
                    ]);

                    // Create new record if not found
                    if ($linkRecord === null) {
                        $linkRecord = new LinkRecord();
                        $linkRecord->campaignId = $sendout->campaignId;
                        $linkRecord->url = $url;
                        $linkRecord->title = $title;

                        $linkRecord->save();
                    }

                    // Add link to converted links
                    $this->_links[$key] = $linkRecord->lid;
                }

                $lid = $this->_links[$key];

                // Replace href attribute
                $element->setAttribute('href', $baseUrl.$lid);
            }
        }

        // Save document element to maintain utf-8 encoding (https://gist.github.com/Xeoncross/9401853)
        return $dom->saveHTML($dom->documentElement);
    }
}
