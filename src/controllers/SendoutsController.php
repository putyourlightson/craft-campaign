<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\controllers;

use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\SegmentElement;
use putyourlightson\campaign\elements\SendoutElement;
use putyourlightson\campaign\elements\CampaignElement;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\models\AutomatedScheduleModel;
use putyourlightson\campaign\helpers\LogHelper;

use Craft;
use craft\web\Controller;
use craft\errors\ElementNotFoundException;
use craft\errors\MissingComponentException;
use craft\helpers\DateTimeHelper;
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
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws ForbiddenHttpException
     * @throws \Throwable
     */
    public function actionQueuePendingSendouts()
    {
        // Get plugin settings
        $settings = Campaign::$plugin->getSettings();

        // Verify API key
        $apiKey = Craft::$app->getRequest()->getQueryParam('key');

        if (!$apiKey OR $apiKey != $settings->apiKey) {
            throw new ForbiddenHttpException('Unauthorised access.');
        }

        Campaign::$plugin->sendouts->queuePendingSendouts();

        return $this->asRaw('');
    }

    /**
     * @return string
     *
     * @throws BadRequestHttpException
     * @throws Exception
     * @throws NotFoundHttpException
     * @throws \Twig_Error_Loader
     * @throws InvalidConfigException
     */
    public function actionGetHtmlBody()
    {
        $sendoutId = Craft::$app->getRequest()->getRequiredParam('sendoutId');
        $sendout = Campaign::$plugin->sendouts->getSendoutById($sendoutId);

        if ($sendout === null) {
            throw new NotFoundHttpException('Sendout not found');
        }

        echo $sendout->getHtmlBody();

        exit();
    }

    /**
     * @param string $sendoutType The sendout type
     * @param int|null $sendoutId The sendout’s ID, if editing an existing sendout.
     * @param SendoutElement|null $sendout The sendout being edited, if there were any validation errors.
     * @param AutomatedScheduleModel|null $automatedSchedule The automated schedule, if there were any validation errors.
     *
     * @return Response
     * @throws NotFoundHttpException
     * @throws ForbiddenHttpException
     */
    public function actionEditSendout(string $sendoutType, int $sendoutId = null, SendoutElement $sendout = null, AutomatedScheduleModel $automatedSchedule = null): Response
    {
        // Require permission
        $this->requirePermission('campaign-sendouts');

        // Check that the sendout type exists
        // ---------------------------------------------------------------------

        $sendoutTypes = SendoutElement::getSendoutTypes();

        if (empty($sendoutTypes[$sendoutType])) {
            throw new NotFoundHttpException('Sendout type not found');
        }

        // Get the sendout
        // ---------------------------------------------------------------------

        if ($sendout === null) {
            if ($sendoutId !== null) {
                $sendout = Campaign::$plugin->sendouts->getSendoutById($sendoutId);

                if ($sendout === null) {
                    throw new NotFoundHttpException('Sendout not found');
                }
            }
            else {
                $sendout = new SendoutElement();
                $sendout->sendoutType = $sendoutType;

                // If a campaign ID was passed in as a param
                $campaignId = Craft::$app->getRequest()->getParam('campaignId');
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

        // Get the automated schedule
        if ($sendoutType == 'automated' AND $automatedSchedule === null) {
            $automatedSchedule = new AutomatedScheduleModel($sendout->automatedSchedule);
        }

        // Set the variables
        // ---------------------------------------------------------------------

        $variables = [
            'sendoutType' => $sendoutType,
            'sendoutId' => $sendoutId,
            'sendout' => $sendout,
            'automatedSchedule' => $automatedSchedule,
        ];

        // Campaign element selector variables
        $variables['campaignElementType'] = CampaignElement::class;
        $variables['campaignElementCriteria'] = [
            'status' => ['active', 'pending'],
        ];

        // Mailing list element selector variables
        $variables['mailingListElementType'] = MailingListElement::class;
        $variables['mailingListElementCriteria'] = [
            'status' => 'enabled',
        ];

        if (Campaign::$plugin->isPro()) {
            // Segment element selector variables
            $variables['segmentElementType'] = SegmentElement::class;
            $variables['segmentElementCriteria'] = [
                'status' => 'enabled',
            ];
        }

        // Contact element selector variables
        $variables['contactElementType'] = ContactElement::class;
        $variables['contactElementCriteria'] = [
            'status' => ['active'],
        ];

        // Get test contact based on current user's email address
        $currentUser = Craft::$app->user->getIdentity();
        $variables['testContact'] = $currentUser ? Campaign::$plugin->contacts->getContactByEmail($currentUser->email) : null;

        // Determine which actions should be available
        // ---------------------------------------------------------------------

        $variables['actions'] = [];

        if ($sendout->isPausable()) {
            $variables['actions'][] = [
                'action' => 'campaign/sendouts/pause-sendout',
                'redirect' => $sendout->getCpEditUrl(),
                'label' => Craft::t('campaign', 'Pause and Edit…'),
                'confirm' => Craft::t('campaign', 'Are you sure you want to pause and edit this sendout?')
            ];
            $variables['actions'][] = [
                'action' => 'campaign/sendouts/pause-sendout',
                'redirect' => 'campaign/sendouts',
                'label' => Craft::t('campaign', 'Pause…'),
                'confirm' => Craft::t('campaign', 'Are you sure you want to pause this sendout?')
            ];
        }

        if ($sendout->isCancellable()) {
            $variables['actions'][] = [
                'action' => 'campaign/sendouts/cancel-sendout',
                'redirect' => 'campaign/sendouts',
                'label' => Craft::t('campaign', 'Cancel…'),
                'confirm' => Craft::t('campaign', 'Are you sure you want to cancel this sendout?')
            ];
        }

        $variables['actions'][] = [
            'action' => 'campaign/sendouts/delete-sendout',
            'redirect' => 'campaign/sendouts',
            'label' => Craft::t('campaign', 'Delete…'),
            'confirm' => Craft::t('campaign', 'Are you sure you want to delete this sendout? This action cannot be undone.')
        ];

        // Get the time delay intervals
        $variables['timeDelayIntervals'] = [
            60 => Craft::t('campaign', 'minute(s)'),
            3600 => Craft::t('campaign', 'hour(s)'),
            86400 => Craft::t('campaign', 'day(s)'),
            604800 => Craft::t('campaign', 'week(s)'),
        ];

        // Get the settings
        $variables['settings'] = Campaign::$plugin->getSettings();

        // Set full page form variable
        $variables['fullPageForm'] = true;

        // Render the template
        if ($sendout->getIsEditable() AND !Craft::$app->request->getParam('preview')) {
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
        $this->requirePermission('campaign-sendouts');

        $this->requirePostRequest();

        $sendoutId = Craft::$app->getRequest()->getBodyParam('sendoutId');

        if ($sendoutId) {
            $sendout = $this->_getSendoutFromPostedId();
        }
        else {
            $sendout = new SendoutElement();
        }

        $sendout->title = Craft::$app->getRequest()->getBodyParam('title', $sendout->title);
        $sendout->sendoutType = Craft::$app->getRequest()->getBodyParam('sendoutType', $sendout->sendoutType);
        $sendout->fromName = Craft::$app->getRequest()->getBodyParam('fromName', $sendout->fromName);
        $sendout->fromEmail = Craft::$app->getRequest()->getBodyParam('fromEmail', $sendout->fromEmail);
        $sendout->subject = Craft::$app->getRequest()->getBodyParam('subject', $sendout->subject);
        $sendout->notificationEmailAddress = Craft::$app->getRequest()->getBodyParam('notificationEmailAddress', $sendout->notificationEmailAddress);
        $sendout->googleAnalyticsLinkTracking = (bool)Craft::$app->getRequest()->getBodyParam('googleAnalyticsLinkTracking', $sendout->googleAnalyticsLinkTracking);

        // Get the selected campaign ID
        $sendout->campaignId = Craft::$app->getRequest()->getBodyParam('campaignId', $sendout->campaignId);
        $sendout->campaignId = (\is_array($sendout->campaignId) AND isset($sendout->campaignId[0])) ? $sendout->campaignId[0] : null;

        // Get the selected included and excluded mailing list IDs and segment IDs
        $sendout->mailingListIds = Craft::$app->getRequest()->getBodyParam('mailingListIds', $sendout->mailingListIds);
        $sendout->mailingListIds = \is_array($sendout->mailingListIds) ? implode(',', $sendout->mailingListIds) : '';
        $sendout->excludedMailingListIds = Craft::$app->getRequest()->getBodyParam('excludedMailingListIds', $sendout->excludedMailingListIds);
        $sendout->excludedMailingListIds = \is_array($sendout->excludedMailingListIds) ? implode(',', $sendout->excludedMailingListIds) : '';

        $sendout->segmentIds = Craft::$app->getRequest()->getBodyParam('segmentIds', $sendout->segmentIds);
        $sendout->segmentIds = \is_array($sendout->segmentIds) ? implode(',', $sendout->segmentIds) : '';

        // Convert send date
        if ($sendout->sendoutType == 'scheduled') {
            $sendout->sendDate = Craft::$app->getRequest()->getBodyParam('sendDate', $sendout->sendDate);
            $sendout->sendDate = DateTimeHelper::toDateTime($sendout->sendDate);
            $sendout->sendDate = ($sendout->sendDate === false) ? null : $sendout->sendDate;
        }
        else {
            $sendout->sendDate = $sendout->sendDate ?? new \DateTime();
        }

        // Get automated fields
        $automatedSchedule = null;

        if ($sendout->sendoutType == 'automated' AND Campaign::$plugin->isPro()) {
            $sendout->automatedSchedule = Craft::$app->getRequest()->getBodyParam('automatedSchedule', $sendout->automatedSchedule);

            // Create automated schedule model
            $automatedSchedule = new AutomatedScheduleModel($sendout->automatedSchedule);

            // Validate automated schedule and sendout
            $automatedSchedule->validate();
            $sendout->validate();

            // If errors then send the sendout and automated schedule back to the template
            if ($automatedSchedule->hasErrors() OR $sendout->hasErrors()) {
                Craft::$app->getSession()->setError(Craft::t('campaign', 'Couldn’t save sendout.'));

                Craft::$app->getUrlManager()->setRouteParams([
                    'sendout' => $sendout,
                    'automatedSchedule' => $automatedSchedule
                ]);

                return null;
            }
        }

        // Save it
        if (!Craft::$app->getElements()->saveElement($sendout)) {
            Craft::$app->getSession()->setError(Craft::t('campaign', 'Couldn’t save sendout.'));

            // Send the sendout and automated schedule back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'sendout' => $sendout,
                'automatedSchedule' => $automatedSchedule
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
        $this->requirePermission('campaign-sendSendouts');

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
        LogHelper::logUserAction('Sendout "{title}" initiated by "{username}".', ['title' => $sendout->title], __METHOD__);

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
     * @throws \Twig_Error_Loader
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
        LogHelper::logUserAction('Sendout "{title}" paused by "{username}".', ['title' => $sendout->title], __METHOD__);

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
        LogHelper::logUserAction('Sendout "{title}" cancelled by "{username}".', ['title' => $sendout->title], __METHOD__);

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
        LogHelper::logUserAction('Sendout "{title}" deleted by "{username}".', ['title' => $sendout->title], __METHOD__);

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
            throw new NotFoundHttpException('Sendout not found');
        }

        return $sendout;
    }
}
