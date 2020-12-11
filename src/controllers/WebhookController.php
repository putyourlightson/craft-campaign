<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\controllers;

use Aws\Sns\Exception\InvalidSnsMessageException;
use Aws\Sns\Message;
use Aws\Sns\MessageValidator;
use GuzzleHttp\Exception\ConnectException;
use putyourlightson\campaign\Campaign;

use Craft;
use craft\errors\ElementNotFoundException;
use craft\helpers\Json;
use craft\web\Controller;
use Throwable;
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

    /**
     * @inheritdoc
     */
    public $enableCsrfValidation = false;

    /**
     * @var bool Disable Snaptcha validation
     */
    public $enableSnaptchaValidation = false;

    /**
     * @inheritdoc
     */
    protected $allowAnonymous = ['test', 'amazon-ses', 'mailgun', 'mandrill', 'postmark', 'sendgrid'];

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     *
     * @throws ForbiddenHttpException
     */
    public function init()
    {
        parent::init();

        // Verify API key
        $key = Craft::$app->getRequest()->getParam('key');
        $apiKey = Craft::parseEnv(Campaign::$plugin->getSettings()->apiKey);

        if ($key === null || empty($apiKey) || $key != $apiKey) {
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
     * https://docs.aws.amazon.com/ses/latest/DeveloperGuide/notification-examples.html
     *
     * @throws Throwable
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
            $client = Craft::createGuzzleClient([
                'timeout' => 5,
                'connect_timeout' => 5,
            ]);

            try {
                $client->get($message['SubscribeURL']);
            }
            catch (ConnectException $e) {}
        }

        if ($message['Type'] === 'Notification') {
            $body = Json::decodeIfJson($message['Message']);
            $eventType = $body['notificationType'] ?? null;

            if ($eventType == 'Complaint') {
                $email = $body['complaint']['complainedRecipients'][0]['emailAddress'];
                return $this->_callWebhook('complained', $email);
            }
            if ($eventType == 'Bounce' && $body['bounce']['bounceType'] == 'Permanent') {
                $email = $body['bounce']['bouncedRecipients'][0]['emailAddress'];
                return $this->_callWebhook('bounced', $email);
            }
        }

        return $this->asJson(['success' => false, 'error' => Craft::t('campaign', 'Event not found.')]);
    }

    /**
     * Mailgun
     *
     * @throws Throwable
     */
    public function actionMailgun(): Response
    {
        $this->requirePostRequest();

        // Get event data from raw body
        $request = Craft::$app->getRequest();
        $body = Json::decodeIfJson($request->getRawBody());
        $eventData = $body['event-data'] ?? null;

        $eventType = $eventData['event'] ?? '';
        $severity = $eventData['severity'] ?? '';
        $reason = $eventData['reason'] ?? '';
        $email = $eventData['recipient'] ?? '';

        if ($eventData === null) {
            // Get event data from body params (legacy webhooks)
            $eventType = $request->getBodyParam('event');
            $email = $request->getBodyParam('recipient');
        }

        if ($eventType == 'complained') {
            return $this->_callWebhook('complained', $email);
        }

        // Only mark as bounced if the reason indicates that it is a hard bounce.
        // https://github.com/putyourlightson/craft-campaign/issues/178
        if ($eventType == 'failed' && $severity == 'permanent'
            && ($reason == 'bounce' || $reason == 'suppress-bounce')) {
            return $this->_callWebhook('bounced', $email);
        }

        // Legacy webhook
        if ($eventType == 'bounced') {
            return $this->_callWebhook('bounced', $email);
        }

        return $this->asJson(['success' => false, 'error' => Craft::t('campaign', 'Event not found.')]);
    }

    /**
     * Mandrill
     *
     * @throws Throwable
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

                if ($eventType == 'spam') {
                    return $this->_callWebhook('complained', $email);
                }
                if ($eventType == 'hard_bounce') {
                    return $this->_callWebhook('bounced', $email);
                }
            }
        }

        return $this->asJson(['success' => false, 'error' => Craft::t('campaign', 'Event not found.')]);
    }

    /**
     * Postmark
     *
     * @throws Throwable
     */
    public function actionPostmark(): Response
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $eventType = $request->getBodyParam('Type');
        $email = $request->getBodyParam('Email');

        if ($eventType == 'SpamComplaint') {
            return $this->_callWebhook('complained', $email);
        }
        if ($eventType == 'HardBounce') {
            return $this->_callWebhook('bounced', $email);
        }

        return $this->asJson(['success' => false, 'error' => Craft::t('campaign', 'Event not found.')]);
    }

    /**
     * Sendgrid
     *
     * @throws Throwable
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

                if ($eventType == 'complained') {
                    return $this->_callWebhook('complained', $email);
                }
                if ($eventType == 'bounced') {
                    return $this->_callWebhook('bounced', $email);
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
     *
     * @return Response
     * @throws Throwable
     * @throws ElementNotFoundException
     * @throws Exception
     */
    private function _callWebhook(string $event, string $email = null): Response
    {
        // Log request
        Craft::warning('Webhook request: '.Craft::$app->getRequest()->getRawBody(), 'campaign');

        if ($email === null) {
            return $this->asJson(['success' => false, 'error' => Craft::t('campaign', 'Email not found.')]);
        }

        $contact = Campaign::$plugin->contacts->getContactByEmail($email);

        if ($contact === null) {
            return $this->asJson(['success' => false, 'error' => Craft::t('campaign', 'Contact not found.')]);
        }

        if ($event == 'complained') {
            Campaign::$plugin->webhook->complain($contact);
        }
        else if ($event == 'bounced') {
            Campaign::$plugin->webhook->bounce($contact);
        }

        return $this->asJson(['success' => true]);
    }
}
