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
     * Amazon SES
     *
     * @throws \Throwable
     */
    public function actionAmazonSes()
    {
        $this->requirePostRequest();

        $event = Craft::$app->getRequest()->getRawBody();
        $event = Json::decode($event);

        if (is_array($event)) {
            $eventType = $event['notificationType'];

            // Look for SID in headers (requires that "Include Original Headers" is enabled in SES notification settings)
            $sid = '';
            foreach ($event['mail']['headers'] as $header) {
                if ($header['name'] == 'putyourlightson-campaign-sid') {
                    $sid = $header['value'];
                    break;
                }
            }

            if ($eventType == 'Complaint') {
                $email = $event['complaint']['complainedRecipients'][0]['emailAddress'];
                return $this->_callWebhook('complained', $email, $sid);
            }
            if ($eventType == 'Bounce' AND $event['bounce']['bounceType'] == 'Permanent') {
                $email = $event['bounce']['bouncedRecipients'][0]['emailAddress'];
                return $this->_callWebhook('bounced', $email, $sid);
            }
        }

        return $this->asJson(['success' => false, 'error' => Craft::t('campaign', 'Event not found.')]);
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
        $eventType = $request->getBodyParam('event');
        $email = $request->getBodyParam('recipient');
        $sid = $request->getBodyParam('putyourlightson-campaign-sid');

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
    public function actionMandrill()
    {
        $this->requirePostRequest();

        $events = Craft::$app->getRequest()->getBodyParam('mandrill_events');
        $events = Json::decodeIfJson($events);

        if (is_array($events)) {
            foreach ($events as $event) {
                $eventType = $event['event'] ?? '';
                $email = $event['msg']['email'] ?? '';
                $sid = $event['msg']['metadata']['putyourlightson-campaign-sid'] ?? '';

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
    public function actionPostmark()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $eventType = $request->getBodyParam('Type');
        $email = $request->getBodyParam('Email');
        $sid = $request->getBodyParam('putyourlightson-campaign-sid');

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
    public function actionSendgrid()
    {
        $this->requirePostRequest();

        $events = Craft::$app->getRequest()->getRawBody();
        $events = Json::decodeIfJson($events);

        if (is_array($events)) {
            foreach ($events as $event) {
                $eventType = $event['event'] ?? '';
                $email = $event['email'] ?? '';
                $sid = $event['putyourlightson-campaign-sid'] ?? '';

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
    private function _callWebhook(string $event, $email = null, $sid = null): Response
    {
        if ($email === null) {
            return $this->asJson(['success' => false, 'error' => Craft::t('campaign', 'Email not found.')]);
        }

        if ($sid === null) {
            return $this->asJson(['success' => false, 'error' => Craft::t('campaign', 'Sendout not found.')]);
        }

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

        $sendout = Campaign::$plugin->sendouts->getSendoutBySid($sid);

        if ($sendout === null) {
            return $this->asJson(['success' => false, 'error' => Craft::t('campaign', 'Sendout not found.')]);
        }

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