<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\controllers;

use Aws\Sns\Exception\InvalidSnsMessageException;
use Aws\Sns\Message;
use Aws\Sns\MessageValidator;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\models\ContactCampaignModel;
use putyourlightson\campaign\records\ContactCampaignRecord;

use Craft;
use craft\errors\ElementNotFoundException;
use craft\helpers\Json;
use craft\web\Controller;
use yii\base\Exception;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

/**
 * WebhookController
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0   
 */
class WebhookController extends Controller
{
    // Properties
    // =========================================================================

    const HEADER_NAME = 'Craft-Campaign-Sid';

    /**
     * @inheritdoc
     */
    public $enableCsrfValidation = false;

    /**
     * @inheritdoc
     */
    protected $allowAnonymous = ['test', 'amazon-ses', 'mailgun', 'mandrill', 'postmark', 'sendgrid'];

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        // Get plugin settings
        $settings = Campaign::$plugin->getSettings();

        // Verify API key
        $apiKey = Craft::$app->getRequest()->getParam('key');

        if ($apiKey === null OR empty($settings->apiKey) OR $apiKey != $settings->apiKey) {
            throw new ForbiddenHttpException('Unauthorised access.');
        }
    }

    /**
     * Test
     */
    public function actionTest(): Response
    {
        return $this->asJson(['success' => true]);
    }

    /**
     * Amazon SES
     *
     * @throws \Throwable
     */
    public function actionAmazonSes(): Response
    {
        $this->requirePostRequest();

        // Instantiate the Message and Validator
        $message = Message::fromRawPostData();
        $validator = new MessageValidator();

        // Validate the message
        try {
           $validator->validate($message);
        }
        catch (InvalidSnsMessageException $e) {
           return $this->asJson(['success' => false, 'error' => Craft::t('campaign', 'SNS message validation error.')]);
        }

        // Check the type of the message and handle the subscription.
        if ($message['Type'] === 'SubscriptionConfirmation') {
            // Confirm the subscription by sending a GET request to the SubscribeURL
            $client = new Client([
                'timeout' => 5,
                'connect_timeout' => 5,
            ]);

            try {
                $response = $client->get($message['SubscribeURL']);
            }
            catch (ConnectException $e) {}
        }

        if ($message['Type'] === 'Notification') {
            /** @var array $headers */
            $headers = $message['mail']['headers'];

            if (is_array($headers)) {
                // Look for SID in headers (requires that "Include Original Headers" is enabled in SES notification settings)
                $sid = '';

                foreach ($headers as $header) {
                    if ($header['name'] == self::HEADER_NAME) {
                        $sid = $header['value'];
                        break;
                    }
                }

                if ($sid != '') {
                    $eventType = $message['notificationType'];

                    if ($eventType == 'Complaint') {
                        $email = $message['complaint']['complainedRecipients'][0]['emailAddress'];
                        return $this->_callWebhook('complained', $email, $sid);
                    }
                    if ($eventType == 'Bounce' AND $message['bounce']['bounceType'] == 'Permanent') {
                        $email = $message['bounce']['bouncedRecipients'][0]['emailAddress'];
                        return $this->_callWebhook('bounced', $email, $sid);
                    }
                }
            }
        }

        return $this->asJson(['success' => false, 'error' => Craft::t('campaign', 'Event not found.')]);
    }

    /**
     * Mailgun
     *
     * @throws \Throwable
     */
    public function actionMailgun(): Response
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $eventType = $request->getBodyParam('event');
        $email = $request->getBodyParam('recipient');
        $headers = Json::decodeIfJson($request->getBodyParam('message-headers'));

        // Look for SID in headers
        $sid = '';
        if (is_array($headers)) {
            foreach ($headers as $header) {
                if ($header[0] == self::HEADER_NAME) {
                    $sid = $header[1];
                    break;
                }
            }
        }

        if ($eventType == 'complained') {
            return $this->_callWebhook('complained', $email, $sid);
        }
        if ($eventType == 'bounced') {
            return $this->_callWebhook('bounced', $email, $sid);
        }

        return $this->asJson(['success' => false, 'error' => Craft::t('campaign', 'Event not found.')]);
    }

    /**
     * Mandrill
     *
     * @throws \Throwable
     */
    public function actionMandrill(): Response
    {
        $this->requirePostRequest();

        $events = Craft::$app->getRequest()->getBodyParam('mandrill_events');
        $events = Json::decodeIfJson($events);

        if (is_array($events)) {
            foreach ($events as $event) {
                $eventType = $event['event'] ?? '';
                $email = $event['msg']['email'] ?? '';
                $sid = $event['msg']['metadata'][self::HEADER_NAME] ?? '';

                if ($eventType == 'spam') {
                    return $this->_callWebhook('complained', $email, $sid);
                }
                if ($eventType == 'hard_bounce') {
                    return $this->_callWebhook('bounced', $email, $sid);
                }
            }
        }

        return $this->asJson(['success' => false, 'error' => Craft::t('campaign', 'Event not found.')]);
    }

    /**
     * Postmark
     *
     * @throws \Throwable
     */
    public function actionPostmark(): Response
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $eventType = $request->getBodyParam('Type');
        $email = $request->getBodyParam('Email');
        $sid = $request->getBodyParam(self::HEADER_NAME);

        if ($eventType == 'SpamComplaint') {
            return $this->_callWebhook('complained', $email, $sid);
        }
        if ($eventType == 'HardBounce') {
            return $this->_callWebhook('bounced', $email, $sid);
        }

        return $this->asJson(['success' => false, 'error' => Craft::t('campaign', 'Event not found.')]);
    }

    /**
     * Sendgrid
     *
     * @throws \Throwable
     */
    public function actionSendgrid(): Response
    {
        $this->requirePostRequest();

        $events = Craft::$app->getRequest()->getRawBody();
        $events = Json::decodeIfJson($events);

        if (is_array($events)) {
            foreach ($events as $event) {
                $eventType = $event['event'] ?? '';
                $email = $event['email'] ?? '';
                $sid = $event[self::HEADER_NAME] ?? '';

                if ($eventType == 'complained') {
                    return $this->_callWebhook('complained', $email, $sid);
                }
                if ($eventType == 'bounced') {
                    return $this->_callWebhook('bounced', $email, $sid);
                }
            }
        }

        return $this->asJson(['success' => false, 'error' => Craft::t('campaign', 'Event not found.')]);
    }

    // Private Methods
    // =========================================================================

    /**
     * Call webhook
     *
     * @param string $event
     * @param string|null $email
     * @param string|null $sid
     *
     * @return Response
     * @throws ForbiddenHttpException
     * @throws \Throwable
     * @throws ElementNotFoundException
     * @throws Exception
     */
    private function _callWebhook(string $event, string $email = null, string $sid = null): Response
    {
        // Log request
        Craft::warning('Webhook request: '.Craft::$app->getRequest()->getRawBody(), 'Campaign');

        if ($email === null) {
            return $this->asJson(['success' => false, 'error' => Craft::t('campaign', 'Email not found.')]);
        }

        if ($sid === null) {
            return $this->asJson(['success' => false, 'error' => Craft::t('campaign', 'Sendout not found.')]);
        }

        $contact = Campaign::$plugin->contacts->getContactByEmail($email);

        if ($contact === null) {
            return $this->asJson(['success' => false, 'error' => Craft::t('campaign', 'Contact not found.')]);
        }

        $sendout = Campaign::$plugin->sendouts->getSendoutBySid($sid);

        if ($sendout === null) {
            return $this->asJson(['success' => false, 'error' => Craft::t('campaign', 'Sendout not found.')]);
        }

        $contactCampaignRecord = ContactCampaignRecord::findOne([
            'contactId' => $contact->id,
            'sendoutId' => $sendout->id,
        ]);

        if ($contactCampaignRecord === null) {
            return $this->asJson(['success' => false, 'error' => Craft::t('campaign', 'Contact not found.')]);
        }

        /** @var ContactCampaignModel $contactCampaign */
        $contactCampaign = ContactCampaignModel::populateModel($contactCampaignRecord, false);

        $mailingList = $contactCampaign->getMailingList();

        if ($event == 'complained') {
            Campaign::$plugin->webhook->complain($contact, $mailingList, $sendout);
        }
        else if ($event == 'bounced') {
            Campaign::$plugin->webhook->bounce($contact, $mailingList, $sendout);
        }

        return $this->asJson(['success' => true]);
    }
}