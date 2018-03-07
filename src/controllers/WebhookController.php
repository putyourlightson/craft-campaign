<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\controllers;

use craft\errors\ElementNotFoundException;
use craft\helpers\Json;
use putyourlightson\campaign\Campaign;

use Craft;
use craft\web\Controller;
use yii\base\Exception;
use yii\web\ForbiddenHttpException;

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
     * @inheritdoc
     */
    protected $allowAnonymous = ['test', 'mailgun', 'mandrill', 'postmark', 'sendgrid'];

    // Public Methods
    // =========================================================================

    /**
     * Test
     */
    public function actionTest()
    {
        exit('Test');
    }

    /**
     * Mailgun
     *
     * @throws \Throwable
     */
    public function actionMailgun()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $event = $request->getBodyParam('event');
        $email = $request->getBodyParam('recipient');
        $sid = $request->getBodyParam('sid');

        if (!$event OR !$email) {
            exit();
        }

        if ($event == 'complained') {
            $this->_callWebhook('complained', $email, $sid);
        }
        else if ($event == 'bounced') {
            $this->_callWebhook('bounced', $email, $sid);
        }

        exit();
    }

    /**
     * Mandrill
     *
     * @throws \Throwable
     */
    public function actionMandrill()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $event = $request->getBodyParam('event');
        $message = $request->getBodyParam('msg');
        $email = $message['email'] ?? '';
        $sid = $message['metadata']['sid'] ?? '';

        if (!$event OR !$email OR !$email) {
            exit();
        }

        if ($event == 'spam') {
            $this->_callWebhook('complained', $email, $sid);
        }
        else if ($event == 'hard_bounce') {
            $this->_callWebhook('bounced', $email, $sid);
        }

        exit();
    }

    /**
     * Postmark
     *
     * @throws \Throwable
     */
    public function actionPostmark()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $event = $request->getBodyParam('Type');
        $email = $request->getBodyParam('Email');
        $sid = $request->getBodyParam('sid');

        if (!$event OR !$email) {
            exit();
        }

        if ($event == 'SpamComplaint') {
            $this->_callWebhook('complained', $email, $sid);
        }
        else if ($event == 'HardBounce') {
            $this->_callWebhook('bounced', $email, $sid);
        }
    }

    /**
     * Sendgrid
     *
     * @throws \Throwable
     */
    public function actionSendgrid()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $events = $request->getRawBody();
        $events = Json::decodeIfJson($events);

        /** @var array $events */
        foreach ($events as $event) {
            $email = $event['email'];
            $sid = $event['sid'] ?? '';

            if ($event['event'] == 'complained') {
                $this->_callWebhook('complained', $email, $sid);
            }
            else if ($event['event'] == 'bounced') {
                $this->_callWebhook('bounced', $email, $sid);
            }
        }
    }

    // Private Methods
    // =========================================================================

    /**
     * Call webhook
     *
     * @param string $event
     * @param string $email
     * @param string|null $sid
     *
     * @throws ForbiddenHttpException
     * @throws \Throwable
     * @throws ElementNotFoundException
     * @throws Exception
     */
    private function _callWebhook(string $event, string $email, $sid)
    {
        // Get plugin settings
        $settings = Campaign::$plugin->getSettings();

        // Verify API key
        $apiKey = Craft::$app->getRequest()->getQueryParam('key');

        if (!$apiKey OR $apiKey != $settings->apiKey) {
            throw new ForbiddenHttpException('Unauthorised access.');
        }

        // Get contact
        $contact = Campaign::$plugin->contacts->getContactByEmail($email);

        // Ensure contact exists
        if ($contact === null) {
            exit();
        }

        // Get sendout by SID
        $sendout = $sid ? Campaign::$plugin->sendouts->getSendoutBySid($sid) : null;

        // Get first mailing list in sendout that this contact is subscribed to
        $mailingList = $sendout !== null ? $contact->getSubscribedMailingListInSendout($sendout) : null;

        if ($event == 'complained') {
            Campaign::$plugin->webhook->complain($contact, $mailingList, $sendout);
        }
        else if ($event == 'bounced') {
            Campaign::$plugin->webhook->bounce($contact, $mailingList, $sendout);
        }

        exit();
    }
}