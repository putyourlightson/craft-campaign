<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\controllers;

use craft\web\View;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\SegmentElement;
use putyourlightson\campaign\elements\SendoutElement;
use putyourlightson\campaign\elements\CampaignElement;
use putyourlightson\campaign\elements\MailingListElement;

use Craft;
use craft\web\Controller;
use craft\errors\ElementNotFoundException;
use craft\errors\MissingComponentException;
use craft\helpers\App;
use craft\helpers\DateTimeHelper;
use putyourlightson\campaign\models\AutomatedScheduleModel;
use putyourlightson\campaign\models\RecurringScheduleModel;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * SendoutsController
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 */
class SendoutsController extends Controller
{
    // Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected $allowAnonymous = ['queue-pending-sendouts'];

    // Public Methods
    // =========================================================================

    /**
     * Queues pending sendouts
     *
     * @return Response
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws ForbiddenHttpException
     * @throws \Throwable
     */
    public function actionQueuePendingSendouts(): Response
    {
        $request = Craft::$app->getRequest();

        // Get plugin settings
        $settings = Campaign::$plugin->getSettings();

        // Verify API key
        $apiKey = $request->getParam('key');

        if (!$apiKey OR $apiKey != $settings->apiKey) {
            throw new ForbiddenHttpException('Unauthorised access.');
        }

        $count = Campaign::$plugin->sendouts->queuePendingSendouts();

        if ($request->getParam('run')) {
            App::maxPowerCaptain();

            Craft::$app->getQueue()->run();
        }

        // If front-end site request
        if (Craft::$app->getView()->templateMode == View::TEMPLATE_MODE_SITE) {
            // Prep the response
            $response = Craft::$app->getResponse();
            $response->content = $count;

            return $response;
        }

        if ($request->getAcceptsJson()) {
            return $this->asJson(['success' => true]);
        }

        Craft::$app->getSession()->setNotice(Craft::t('campaign', '{count} pending sendouts queued.', ['count' => $count]));

        return $this->redirectToPostedUrl();
    }

    /**
     * @return Response
     * @throws BadRequestHttpException
     * @throws Exception
     * @throws NotFoundHttpException
     * @throws InvalidConfigException
     */
    public function actionGetHtmlBody(): Response
    {
        $sendoutId = Craft::$app->getRequest()->getRequiredParam('sendoutId');
        $sendout = Campaign::$plugin->sendouts->getSendoutById($sendoutId);

        if ($sendout === null) {
            throw new NotFoundHttpException(Craft::t('campaign', 'Sendout not found.'));
        }

        // Prep the response
        $response = Craft::$app->getResponse();
        $response->content = $sendout->getHtmlBody();

        return $response;
    }

    /**
     * @param string $sendoutType The sendout type
     * @param int|null $sendoutId The sendout’s ID, if editing an existing sendout.
     * @param SendoutElement|null $sendout The sendout being edited, if there were any validation errors.
     *
     * @return Response
     * @throws NotFoundHttpException
     * @throws ForbiddenHttpException
     * @throws InvalidConfigException
     */
    public function actionEditSendout(string $sendoutType, int $sendoutId = null, SendoutElement $sendout = null): Response
    {
        // Require permission
        $this->requirePermission('campaign:sendouts');

        $request = Craft::$app->getRequest();

        // Check that the sendout type exists
        // ---------------------------------------------------------------------

        $sendoutTypes = SendoutElement::sendoutTypes();

        if (empty($sendoutTypes[$sendoutType])) {
            throw new NotFoundHttpException(Craft::t('campaign', 'Sendout type not found.'));
        }

        // Get the sendout
        // ---------------------------------------------------------------------

        if ($sendout === null) {
            if ($sendoutId !== null) {
                $sendout = Campaign::$plugin->sendouts->getSendoutById($sendoutId);

                if ($sendout === null) {
                    throw new NotFoundHttpException(Craft::t('campaign', 'Sendout not found.'));
                }
            }
            else {
                $sendout = new SendoutElement();
                $sendout->sendoutType = $sendoutType;

                // If a campaign ID was passed in as a param
                $campaignId = $request->getParam('campaignId');
                if ($campaignId) {
                    // Set campaign ID
                    $sendout->campaignId = $campaignId;

                    // Set title and subject to campaign title
                    $campaign = $sendout->getCampaign();
                    $sendout->title = $campaign !== null ? $campaign->title : '';
                    $sendout->subject = $sendout->title;
                }
            }
        }

        // Get the schedule
        if ($sendoutType == 'automated') {
            $sendout->schedule = new AutomatedScheduleModel($sendout->schedule);
        }
        else if ($sendoutType == 'recurring') {
            $sendout->schedule = new RecurringScheduleModel($sendout->schedule);
        }

        // Set the variables
        // ---------------------------------------------------------------------

        $variables = [
            'sendoutType' => $sendoutType,
            'sendoutId' => $sendoutId,
            'sendout' => $sendout,
        ];

        // Campaign element selector variables
        $variables['campaignElementType'] = CampaignElement::class;
        $variables['campaignElementCriteria'] = [
            'status' => [CampaignElement::STATUS_SENT, CampaignElement::STATUS_PENDING],
        ];

        // Mailing list element selector variables
        $variables['mailingListElementType'] = MailingListElement::class;
        $variables['mailingListElementCriteria'] = [
            'status' => MailingListElement::STATUS_ENABLED,
        ];

        if (Campaign::$plugin->getIsPro()) {
            // Segment element selector variables
            $variables['segmentElementType'] = SegmentElement::class;
            $variables['segmentElementCriteria'] = [
                'status' => SegmentElement::STATUS_ENABLED,
            ];
        }

        // Contact element selector variables
        $variables['contactElementType'] = ContactElement::class;
        $variables['contactElementCriteria'] = [
            'status' => ContactElement::STATUS_ACTIVE,
        ];

        // Get test contact based on current user's email address
        $currentUser = Craft::$app->user->getIdentity();
        $variables['testContact'] = $currentUser ? Campaign::$plugin->contacts->getContactByEmail($currentUser->email) : null;

        // Determine which actions should be available
        // ---------------------------------------------------------------------

        $variables['actions'] = [];

        if ($sendout->getIsPausable()) {
            $variables['actions'][0][] = [
                'action' => 'campaign/sendouts/pause-sendout',
                'redirect' => $sendout->getCpEditUrl(),
                'label' => Craft::t('campaign', 'Pause and Edit…'),
                'confirm' => Craft::t('campaign', 'Are you sure you want to pause and edit this sendout?'),
            ];
            $variables['actions'][0][] = [
                'action' => 'campaign/sendouts/pause-sendout',
                'redirect' => 'campaign/sendouts',
                'label' => Craft::t('campaign', 'Pause…'),
                'confirm' => Craft::t('campaign', 'Are you sure you want to pause this sendout?'),
            ];
        }

        if ($sendout->getIsCancellable()) {
            $variables['actions'][1][] = [
                'action' => 'campaign/sendouts/cancel-sendout',
                'destructive' => 'true',
                'redirect' => 'campaign/sendouts',
                'label' => Craft::t('campaign', 'Cancel…'),
                'confirm' => Craft::t('campaign', 'Are you sure you want to cancel this sendout? It cannot be sent again if cancelled.'),
            ];
        }

        $variables['actions'][1][] = [
            'action' => 'campaign/sendouts/delete-sendout',
            'destructive' => 'true',
            'redirect' => 'campaign/sendouts',
            'label' => Craft::t('campaign', 'Delete…'),
            'confirm' => Craft::t('campaign', 'Are you sure you want to delete this sendout? This action cannot be undone.'),
        ];

        if ($sendoutType == 'automated' OR $sendoutType == 'recurring') {
            // Get the interval options
            $variables['intervalOptions'] = $sendout->schedule->getIntervalOptions();
        }

        // Get the settings
        $variables['settings'] = Campaign::$plugin->getSettings();

        // Set full page form variable
        $variables['fullPageForm'] = true;

        // Render the template
        if ($sendout->getIsEditable() AND !$request->getParam('preview')) {
            return $this->renderTemplate('campaign/sendouts/_edit', $variables);
        }

        // Call for max power
        Campaign::$plugin->maxPowerLieutenant();

        // Get the system limits
        $variables['system'] = [
            'memoryLimit' => ini_get('memory_limit'),
            'timeLimit' => ini_get('max_execution_time'),
        ];

        return $this->renderTemplate('campaign/sendouts/_view', $variables);
    }

    /**
     * @return Response|null
     *
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     * @throws \Throwable
     * @throws ElementNotFoundException
     * @throws Exception
     */
    public function actionSaveSendout()
    {
        // Require permission
        $this->requirePermission('campaign:sendouts');

        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $sendoutId = $request->getBodyParam('sendoutId');

        if ($sendoutId) {
            $sendout = $this->_getSendoutFromPostedId();
        }
        else {
            $sendout = new SendoutElement();
        }

        $sendout->title = $request->getBodyParam('title', $sendout->title);
        $sendout->sendoutType = $request->getBodyParam('sendoutType', $sendout->sendoutType);
        $sendout->fromName = $request->getBodyParam('fromName', $sendout->fromName);
        $sendout->fromEmail = $request->getBodyParam('fromEmail', $sendout->fromEmail);
        $sendout->subject = $request->getBodyParam('subject', $sendout->subject);
        $sendout->notificationEmailAddress = $request->getBodyParam('notificationEmailAddress', $sendout->notificationEmailAddress);
        $sendout->googleAnalyticsLinkTracking = (bool)$request->getBodyParam('googleAnalyticsLinkTracking', $sendout->googleAnalyticsLinkTracking);

        // Get the selected campaign ID
        $sendout->campaignId = $request->getBodyParam('campaignId', $sendout->campaignId);
        $sendout->campaignId = (\is_array($sendout->campaignId) AND isset($sendout->campaignId[0])) ? $sendout->campaignId[0] : null;

        // Get the selected included and excluded mailing list IDs and segment IDs
        $sendout->mailingListIds = $request->getBodyParam('mailingListIds', $sendout->mailingListIds);
        $sendout->mailingListIds = \is_array($sendout->mailingListIds) ? implode(',', $sendout->mailingListIds) : '';
        $sendout->excludedMailingListIds = $request->getBodyParam('excludedMailingListIds', $sendout->excludedMailingListIds);
        $sendout->excludedMailingListIds = \is_array($sendout->excludedMailingListIds) ? implode(',', $sendout->excludedMailingListIds) : '';

        $sendout->segmentIds = $request->getBodyParam('segmentIds', $sendout->segmentIds);
        $sendout->segmentIds = \is_array($sendout->segmentIds) ? implode(',', $sendout->segmentIds) : '';

        // Convert send date
        $sendout->sendDate = $request->getBodyParam('sendDate', $sendout->sendDate);
        $sendout->sendDate = DateTimeHelper::toDateTime($sendout->sendDate);
        $sendout->sendDate = $sendout->sendDate ?: new \DateTime();

        if ($sendout->sendoutType == 'automated' OR $sendout->sendoutType == 'recurring') {
            $schedule = $request->getBodyParam('schedule');

            if ($sendout->sendoutType == 'automated') {
                $sendout->schedule = new AutomatedScheduleModel($schedule);
            }
            else {
                $sendout->schedule = new RecurringScheduleModel($schedule);
            }

            // Convert end date and time of day or set to null
            $sendout->schedule->endDate = DateTimeHelper::toDateTime($sendout->schedule->endDate) ?: null;
            $sendout->schedule->timeOfDay = DateTimeHelper::toDateTime($sendout->schedule->timeOfDay) ?: null;

            // Validate schedule and sendout
            $sendout->schedule->validate();
            $sendout->validate();

            // If errors then send the sendout back to the template
            if ($sendout->schedule->hasErrors() OR $sendout->hasErrors()) {
                Craft::$app->getSession()->setError(Craft::t('campaign', 'Couldn’t save sendout.'));

                Craft::$app->getUrlManager()->setRouteParams([
                    'sendout' => $sendout,
                ]);

                return null;
            }
        }

        // Save it
        if (!Craft::$app->getElements()->saveElement($sendout)) {
            Craft::$app->getSession()->setError(Craft::t('campaign', 'Couldn’t save sendout.'));

            // Send the sendout and schedule back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'sendout' => $sendout,
            ]);

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('campaign', 'Sendout saved.'));

        return $this->redirectToPostedUrl($sendout);
    }

    /**
     * @return Response|null
     *
     * @throws ForbiddenHttpException
     * @throws NotFoundHttpException
     * @throws \Throwable
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws BadRequestHttpException
     */
    public function actionSendSendout()
    {
        // Require permission to send
        $this->requirePermission('campaign:sendSendouts');

        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $sendout = $this->_getSendoutFromPostedId();

        // Store current user ID
        $sendout->senderId = Craft::$app->getUser()->getId();

        // Set status to pending
        $sendout->sendStatus = 'pending';

        // Save it
        if (!Craft::$app->getElements()->saveElement($sendout)) {
            if ($request->getAcceptsJson()) {
                return $this->asJson(['errors' => $sendout->getErrors()]);
            }

            Craft::$app->getSession()->setError(Craft::t('campaign', 'Couldn’t save sendout.'));

            // Send the sendout back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'sendout' => $sendout
            ]);

            return null;
        }

        // Log it
        Campaign::$plugin->logUserAction('Sendout "{title}" initiated by "{username}".', ['title' => $sendout->title], __METHOD__);

        // Queue pending sendouts
        Campaign::$plugin->sendouts->queuePendingSendouts();

        if ($request->getAcceptsJson()) {
            return $this->asJson(['success' => true]);
        }

        Craft::$app->getSession()->setNotice(Craft::t('campaign', 'Sendout saved.'));

        return $this->redirectToPostedUrl($sendout);
    }

    /**
     * Sends a test
     *
     * @return Response
     * @throws BadRequestHttpException
     * @throws Exception
     * @throws InvalidConfigException
     * @throws MissingComponentException
     * @throws NotFoundHttpException
     */
    public function actionSendTest(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $contactId = Craft::$app->getRequest()->getBodyParam('contactId');
        $sendout = $this->_getSendoutFromPostedId();

        // Validate test contact
        if (empty($contactId)) {
            return $this->asJson(['success' => false, 'error' => Craft::t('campaign', 'A contact must be submitted.')]);
        }

        $contact = Campaign::$plugin->contacts->getContactById($contactId);

        if ($contact === null) {
            return $this->asJson(['success' => false, 'error' => Craft::t('campaign', 'Contact not found.')]);
        }

        if (!Campaign::$plugin->sendouts->sendTest($sendout, $contact)) {
            return $this->asJson(['success' => false, 'error' => Craft::t('campaign', 'Couldn’t send test email.')]);
        }

        return $this->asJson(['success' => true]);
    }

    /**
     * Pauses a sendout
     *
     * @return Response|null
     * @throws BadRequestHttpException
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws NotFoundHttpException
     * @throws \Throwable
     */
    public function actionPauseSendout()
    {
        $this->requirePostRequest();

        $sendout = $this->_getSendoutFromPostedId();

        if (!Campaign::$plugin->sendouts->pauseSendout($sendout)) {
            Craft::$app->getSession()->setError(Craft::t('campaign', 'Sendout could not be paused.'));

            return null;
        }

        // Log it
        Campaign::$plugin->logUserAction('Sendout "{title}" paused by "{username}".', ['title' => $sendout->title], __METHOD__);

        Craft::$app->getSession()->setNotice(Craft::t('campaign', 'Sendout paused.'));

        return $this->redirectToPostedUrl();
    }

    /**
     * Cancels a sendout
     *
     * @return Response|null
     * @throws BadRequestHttpException
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws NotFoundHttpException
     * @throws \Throwable
     */
    public function actionCancelSendout()
    {
        $this->requirePostRequest();

        $sendout = $this->_getSendoutFromPostedId();

        if (!Campaign::$plugin->sendouts->cancelSendout($sendout)) {
            Craft::$app->getSession()->setError(Craft::t('campaign', 'Sendout could not be cancelled.'));

            return null;
        }

        // Log it
        Campaign::$plugin->logUserAction('Sendout "{title}" cancelled by "{username}".', ['title' => $sendout->title], __METHOD__);

        Craft::$app->getSession()->setNotice(Craft::t('campaign', 'Sendout cancelled.'));

        return $this->redirectToPostedUrl();
    }

    /**
     * Deletes a sendout
     *
     * @return Response|null
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     * @throws \Throwable
     */
    public function actionDeleteSendout()
    {
        $this->requirePostRequest();

        $sendout = $this->_getSendoutFromPostedId();

        if (!Campaign::$plugin->sendouts->deleteSendout($sendout)) {
            Craft::$app->getSession()->setError(Craft::t('campaign', 'Sendout could not be deleted.'));

            return null;
        }

        // Log it
        Campaign::$plugin->logUserAction('Sendout "{title}" deleted by "{username}".', ['title' => $sendout->title], __METHOD__);

        Craft::$app->getSession()->setNotice(Craft::t('campaign', 'Sendout deleted.'));

        return $this->redirectToPostedUrl();
    }

    // Private Methods
    // =========================================================================

    /**
     * Gets a sendout from a posted ID
     *
     * @return SendoutElement
     *
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     */
    private function _getSendoutFromPostedId(): SendoutElement
    {
        $sendoutId = Craft::$app->getRequest()->getRequiredBodyParam('sendoutId');
        $sendout = Campaign::$plugin->sendouts->getSendoutById($sendoutId);

        if ($sendout === null) {
            throw new NotFoundHttpException(Craft::t('campaign', 'Sendout not found.'));
        }

        return $sendout;
    }
}
