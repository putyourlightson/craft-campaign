<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\services;

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
        if (!$sendoutId) {
            return null;
        }

        $sendout = SendoutElement::find()
            ->id($sendoutId)
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
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws \Throwable
     */
    public function queuePendingSendouts()
    {
        $currentTimeDb = Db::prepareDateForDb(new \DateTime());

        // Find pending sendouts that are ready to be sent
        $sendouts = SendoutElement::find()
            ->where([
                'and',
                ['sendStatus' => 'pending'],
                ['<=', 'sendDate', $currentTimeDb],
            ])
            ->all();

        /** @var SendoutElement[] $sendouts */
        foreach ($sendouts as $sendout) {
            // Queue regular and scheduled sendouts
            if ($sendout->sendoutType == 'regular' OR $sendout->sendoutType == 'scheduled') {
                $this->_queueSendout($sendout);
            }

            // If pro version and sendout is scheduled to send now and there are pending recipients
            else if (Campaign::$plugin->isPro() AND $sendout->isScheduledToSendNow() AND $sendout->hasPendingRecipients()) {
                $this->_queueSendout($sendout);
            }
        }
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
     * @throws \Twig_Error_Loader
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
     * @throws ElementNotFoundException
     * @throws Exception
     */
    public function sendEmail(SendoutElement $sendout, ContactElement $contact, int $mailingListId)
    {
        if ($sendout->isSendable() === false) {
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

        // Create contact campaign record
        $contactCampaignRecord = new ContactCampaignRecord();
        $contactCampaignRecord->contactId = $contact->id;
        $contactCampaignRecord->campaignId = $campaign->id;
        $contactCampaignRecord->sendoutId = $sendout->id;
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

        // Fire a 'beforeSendEmail' event
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

            // Update recipients
            $sendout->recipients++;

            // Update last sent
            $sendout->lastSent = new \DateTime();
        }
        else {
            // Update failed date
            $contactCampaignRecord->failed = new \DateTime();

            // Update failed recipients
            $sendout->failedRecipients++;

            // Change status to failed and add status message
            $sendout->sendStatus = 'failed';
            $sendout->sendStatusMessage = Craft::t('campaign', 'Sending failed. Please check  your email settings.', ['email' => $contact->email]);
        }

        // Save contact campaign record
        $contactCampaignRecord->save();

        // Save sendout
        Craft::$app->getElements()->saveElement($sendout);

        // Fire an 'afterSendEmail' event
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
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws \Throwable
     */
    public function prepareSending(SendoutElement $sendout)
    {
        // Update send status
        if ($sendout->sendStatus != 'sending') {
            $sendout->sendStatus = 'sending';
            Craft::$app->getElements()->saveElement($sendout);
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
        $sendout->sendStatus = 'pending';

        // Update send status if fully complete and not automated or recurring
        if (!$sendout->hasPendingRecipients() AND $sendout->sendoutType != 'automated' AND $sendout->sendoutType != 'recurring') {
            $sendout->sendStatus = 'sent';
        }

        // Get campaign
        $campaign = $sendout->getCampaign();

        if ($campaign === null) {
            return;
        }

        // Update HTML and plaintext body
        $contact = new ContactElement();
        $sendout->htmlBody = $campaign->getHtmlBody($contact, $sendout);
        $sendout->plaintextBody = $campaign->getPlaintextBody($contact, $sendout);

        Craft::$app->getElements()->saveElement($sendout);

        // Update campaign recipients
        $campaign->recipients += $sendout->recipients;

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
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws \Throwable
     */
    public function pauseSendout(SendoutElement $sendout): bool
    {
        if ($sendout->isPausable()) {
            $sendout->sendStatus = 'paused';

            return Craft::$app->getElements()->saveElement($sendout);
        }

        return false;
    }

    /**
     * Cancels a sendout
     *
     * @param SendoutElement $sendout
     *
     * @return bool Whether the action was successful
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws \Throwable
     */
    public function cancelSendout(SendoutElement $sendout): bool
    {
        if ($sendout->isCancellable()) {
            $sendout->sendStatus = 'cancelled';

            return Craft::$app->getElements()->saveElement($sendout);
        }

        return false;
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
        if ($sendout->isDeletable()) {
            return Craft::$app->getElements()->deleteElement($sendout);
        }

        return false;
    }

    // Private Methods
    // =========================================================================

    /**
     * Queue sendout
     *
     * @param SendoutElement $sendout
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws \Throwable
     */
    private function _queueSendout(SendoutElement $sendout)
    {
        // Add sendout job to queue
        Craft::$app->getQueue()->push(new SendoutJob([
            'sendoutId' => $sendout->id,
            'title' => $sendout->title,
        ]));

        // Update send status
        $sendout->sendStatus = 'queued';

        // Save sendout
        Craft::$app->getElements()->saveElement($sendout);
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

        // Get all links in body
        $links = [];

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

                $links[$key] = [
                    'url' => $url,
                    'title' => $title,
                ];

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
