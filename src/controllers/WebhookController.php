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
use putyourlightson\campaign\models\ContactCampaignModel;
use putyourlightson\campaign\records\ContactCampaignRecord;
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
        return $this->asJson(['success' => true]);
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
            return $this->asJson(['success' => false, 'error' => Craft::t('campaign', 'Email not found.')]);
        }

        if ($event == 'complained') {
            return $this->_callWebhook('complained', $email, $sid);
        }
        if ($event == 'bounced') {
            return $this->_callWebhook('bounced', $email, $sid);
        }

        return $this->asJson(['success' => false, 'error' => Craft::t('campaign', 'Event not found.')]);
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
            return $this->asJson(['success' => false, 'error' => Craft::t('campaign', 'Email not found.')]);
        }

        if ($event == 'spam') {
            return $this->_callWebhook('complained', $email, $sid);
        }
        if ($event == 'hard_bounce') {
            return $this->_callWebhook('bounced', $email, $sid);
        }

        return $this->asJson(['success' => false, 'error' => Craft::t('campaign', 'Event not found.')]);
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
            return $this->asJson(['success' => false, 'error' => Craft::t('campaign', 'Email not found.')]);
        }

        if ($event == 'SpamComplaint') {
            return $this->_callWebhook('complained', $email, $sid);
        }
        if ($event == 'HardBounce') {
            return $this->_callWebhook('bounced', $email, $sid);
        }

        return $this->asJson(['success' => false, 'error' => Craft::t('campaign', 'Event not found.')]);
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
                return $this->_callWebhook('complained', $email, $sid);
            }
            if ($event['event'] == 'bounced') {
                return $this->_callWebhook('bounced', $email, $sid);
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
     * @param string $email
     * @param string|null $sid
     *
     * @return Response
     * @throws ForbiddenHttpException
     * @throws \Throwable
     * @throws ElementNotFoundException
     * @throws Exception
     */
    private function _callWebhook(string $event, string $email, $sid = null): Response
    {
        // Get plugin settings
        $settings = Campaign::$plugin->getSettings();

        // Verify API key
        $apiKey = Craft::$app->getRequest()->getQueryParam('key');

        if (!$apiKey OR $apiKey != $settings->apiKey) {
            throw new ForbiddenHttpException('Unauthorised access.');
        }

        $contact = Campaign::$plugin->contacts->getContactByEmail($email);

        if ($contact === null) {
            return $this->asJson(['success' => false, 'error' => Craft::t('campaign', 'Contact not found.')]);
        }

        $sendout = $sid ? Campaign::$plugin->sendouts->getSendoutBySid($sid) : null;

        $contactCampaignRecord = ContactCampaignRecord::findOne([
            'contactId' => $contact->id,
            'sendoutId' => $sendout->id,
        ]);

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