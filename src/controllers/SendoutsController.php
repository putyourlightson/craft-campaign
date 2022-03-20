<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\controllers;

use Craft;
use craft\base\Element;
use craft\helpers\App;
use craft\helpers\Cp;
use craft\queue\Queue;
use craft\web\Controller;
use craft\web\CpScreenResponseBehavior;
use craft\web\View;
use putyourlightson\campaign\assets\SendoutPreflightAsset;
use putyourlightson\campaign\assets\SendTestAsset;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\SendoutElement;
use putyourlightson\campaign\fieldlayoutelements\sendouts\SendoutField;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

class SendoutsController extends Controller
{
    /**
     * @inheritdoc
     */
    protected int|bool|array $allowAnonymous = ['queue-pending-sendouts'];

    /**
     * Queues pending sendouts.
     */
    public function actionQueuePendingSendouts(): Response
    {
        // Require permission if posted from utility
        if ($this->request->getIsPost() && $this->request->getParam('utility')) {
            $this->requirePermission('campaign:utility');
        }
        else {
            // Verify API key
            $key = $this->request->getParam('key');
            $apiKey = App::parseEnv(Campaign::$plugin->getSettings()->apiKey);

            if ($key === null || empty($apiKey) || $key != $apiKey) {
                throw new ForbiddenHttpException('Unauthorised access.');
            }
        }

        $count = Campaign::$plugin->sendouts->queuePendingSendouts();

        if ($this->request->getParam('run')) {
            /** @var Queue $queue */
            $queue = Craft::$app->getQueue();
            $queue->run();
        }

        // If front-end site request
        if (Craft::$app->getView()->templateMode == View::TEMPLATE_MODE_SITE) {
            // Prep the response
            $this->response->content = $count;

            return $this->response;
        }

        return $this->asSuccess(Craft::t('campaign', '{count} pending sendout(s) queued.', ['count' => $count]));
    }

    /**
     * Returns the pending recipient count.
     */
    public function actionGetPendingRecipientCount(): Response
    {
        $sendout = $this->_getSendoutFromParamId();

        // Prep the response
        $this->response->content = $sendout->getPendingRecipientCount();

        return $this->response;
    }

    /**
     * Returns the HTML body.
     */
    public function actionGetHtmlBody(): Response
    {
        $sendoutId = $this->request->getRequiredParam('sendoutId');
        $sendout = Campaign::$plugin->sendouts->getSendoutById($sendoutId);

        if ($sendout === null) {
            throw new NotFoundHttpException(Craft::t('campaign', 'Sendout not found.'));
        }

        // Prep the response
        $this->response->content = $sendout->getHtmlBody();

        return $this->response;
    }

    /**
     * Creates a new unpublished draft and redirects to its edit page.
     *
     * @see CategoriesController::actionCreate()
     * @since 2.0.0
     */
    public function actionCreate(string $sendoutType = null): Response
    {
        if (!isset(SendoutElement::sendoutTypes()[$sendoutType])) {
            throw new BadRequestHttpException("Invalid sendout type: $sendoutType");
        }

        $site = Cp::requestedSite();

        if (!$site) {
            throw new ForbiddenHttpException('User not authorized to edit content in any sites.');
        }

        $user = Craft::$app->getUser()->getIdentity();

        $sendout = Craft::createObject(SendoutElement::class);
        $sendout->siteId = $site->id;
        $sendout->sendoutType = $sendoutType;

        if (!$sendout->canSave($user)) {
            throw new ForbiddenHttpException('User not authorized to save this sendout.');
        }

        // If a campaign ID was passed in as a parameter (from campaign "save and send" button)
        if ($campaignId = $this->request->getParam('campaignId')) {
            // Set title and subject to campaign title
            $campaign = Campaign::$plugin->campaigns->getCampaignById($campaignId);

            if ($campaign) {
                $sendout->campaignId = $campaign->id;
                $sendout->title = $campaign->title;
                $sendout->subject = $campaign->title;
            }
        }

        // Save it
        $sendout->setScenario(Element::SCENARIO_ESSENTIALS);
        if (!Craft::$app->getDrafts()->saveElementAsDraft($sendout, Craft::$app->getUser()->getId(), null, null, false)) {
            throw new ServerErrorHttpException(sprintf('Unable to save sendout as a draft: %s', implode(', ', $sendout->getErrorSummary(true))));
        }

        // Redirect to its edit page
        return $this->redirect($sendout->getCpEditUrl());
    }

    /**
     * Main edit page.
     */
    public function actionEdit(int $sendoutId): Response
    {
        // Set the selected subnav item by adding it to the global variables
        Craft::$app->view->getTwig()->addGlobal('selectedSubnavItem', 'sendouts');

        /** @var Response|CpScreenResponseBehavior $response */
        $response = Craft::$app->runAction('elements/edit', [
            'elementId' => $sendoutId,
        ]);

        // Use the elements service, in case this is a provisional draft.
        $sendout = Craft::$app->getElements()->getElementById($sendoutId, SendoutElement::class);

        if ($sendout === null) {
            return $response;
        }

        if (!$sendout->getIsModifiable()) {
            return $this->redirect($sendout->getCpPreviewUrl());
        }

        $response->submitButtonLabel = Craft::t('campaign', 'Save and Preview');
        $response->redirectUrl = $sendout->getCpPreviewUrl();

        if ($sendout->getIsPausable()) {
            $response->addAltAction(
                Craft::t('campaign', 'Pause and Edit'),
                [
                    'action' => 'campaign/sendouts/pause',
                    'redirect' => $sendout->getCpEditUrl(),
                    'label' => Craft::t('campaign', 'Pause and Edit'),
                    'confirm' => Craft::t('campaign', 'Are you sure you want to pause and edit this sendout?'),
                ]
            );
            $response->addAltAction(
                Craft::t('campaign', 'Pause'),
                [
                    'action' => 'campaign/sendouts/pause',
                    'redirect' => 'campaign/sendouts',
                    'label' => Craft::t('campaign', 'Pause'),
                    'confirm' => Craft::t('campaign', 'Are you sure you want to pause this sendout?'),
                ]
            );
        }

        if ($sendout->getIsCancellable()) {
            $response->addAltAction(
                Craft::t('campaign', 'Cancel'),
                [
                    'action' => 'campaign/sendouts/cancel',
                    'destructive' => 'true',
                    'redirect' => 'campaign/sendouts',
                    'confirm' => Craft::t('campaign', 'Are you sure you want to cancel this sendout? It cannot be sent again if cancelled.'),
                ]
            );
        }

        $response->addAltAction(
            Craft::t('campaign', 'Cancel'),
            [
                'action' => 'campaign/sendouts/delete',
                'destructive' => 'true',
                'redirect' => 'campaign/sendouts',
                'label' => Craft::t('campaign', 'Delete'),
                'confirm' => Craft::t('campaign', 'Are you sure you want to delete this sendout?'),
            ]
        );

        return $response;
    }

    /**
     * Preview page.
     */
    public function actionPreview(int $sendoutId): Response
    {
        $sendout = Campaign::$plugin->sendouts->getSendoutById($sendoutId);

        if ($sendout === null) {
            throw new BadRequestHttpException("Invalid sendout ID: $sendoutId");
        }

        $this->view->registerAssetBundle(SendoutPreflightAsset::class);
        $this->view->registerAssetBundle(SendTestAsset::class);

        $campaign = $sendout->getCampaign();
        $campaignType = $campaign->getCampaignType();

        $variables = [
            'sendout' => $sendout,
            'settings' => Campaign::$plugin->getSettings(),
            'fullPageForm' => true,
            'contactElementType' => ContactElement::class,
            'contactElementCriteria' => [
                'status' => ContactElement::STATUS_ACTIVE,
            ],
            'testContacts' => $campaignType->getTestContactsWithDefault(),
            'actions' => [],
            'system' => [
                'memoryLimit' => ini_get('memory_limit'),
                'timeLimit' => ini_get('max_execution_time'),
            ],
            'isDynamicWebAliasUsed' => Campaign::$plugin->settings->isDynamicWebAliasUsed($sendout->siteId),
        ];

        // Call for max power
        Campaign::$plugin->maxPowerLieutenant();

        $sendoutField = new SendoutField();
        $sendoutField->formHtml($sendout);

        return $this->renderTemplate('campaign/sendouts/_preview', $variables);
    }

    /**
     * Sends the sendout.
     */
    public function actionSend(): ?Response
    {
        // Require permission to send
        $this->requirePermission('campaign:sendSendouts');

        $this->requirePostRequest();

        $sendout = $this->_getSendoutFromParamId();

        // Store current user ID
        $sendout->senderId = Craft::$app->getUser()->getId();

        // Set status to pending
        $sendout->sendStatus = SendoutElement::STATUS_PENDING;

        // Save it
        if (!Craft::$app->getElements()->saveElement($sendout)) {
            return $this->asModelFailure($sendout, Craft::t('campaign', 'Couldn’t save sendout.'), 'sendout');
        }

        // Log it
        Campaign::$plugin->log('Sendout "{title}" initiated by "{username}".', ['title' => $sendout->title]);

        // Queue pending sendouts
        Campaign::$plugin->sendouts->queuePendingSendouts();

        return $this->asModelSuccess($sendout, Craft::t('campaign', 'Sendout saved.'), 'sendout');
    }

    /**
     * Sends a test.
     */
    public function actionSendTest(): ?Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $contactIds = $this->request->getBodyParam('contactIds');
        $sendout = $this->_getSendoutFromParamId();

        // Validate test contacts
        if (empty($contactIds)) {
            return $this->asFailure(Craft::t('campaign', 'At least one contact must be selected.'));
        }

        $contacts = Campaign::$plugin->contacts->getContactsByIds($contactIds);

        foreach ($contacts as $contact) {
            if (!Campaign::$plugin->sendouts->sendTest($sendout, $contact)) {
                return $this->asFailure(Craft::t('campaign', 'Couldn’t send test email.'));
            }
        }

        return $this->asSuccess(Craft::t('campaign', 'Test email sent.'));
    }

    /**
     * Pauses a sendout.
     */
    public function actionPause(): ?Response
    {
        $this->requirePostRequest();

        $sendout = $this->_getSendoutFromParamId();

        if (!Campaign::$plugin->sendouts->pauseSendout($sendout)) {
            return $this->asFailure(Craft::t('campaign', 'Sendout could not be paused.'));
        }

        // Log it
        Campaign::$plugin->log('Sendout "{title}" paused by "{username}".', ['title' => $sendout->title]);

        return $this->asSuccess(Craft::t('campaign', 'Sendout paused.'));
    }

    /**
     * Cancels a sendout.
     */
    public function actionCancel(): ?Response
    {
        $this->requirePostRequest();

        $sendout = $this->_getSendoutFromParamId();

        if (!Campaign::$plugin->sendouts->cancelSendout($sendout)) {
            return $this->asFailure(Craft::t('campaign', 'Sendout could not be cancelled.'));
        }

        // Log it
        Campaign::$plugin->log('Sendout "{title}" cancelled by "{username}".', ['title' => $sendout->title]);

        return $this->asSuccess(Craft::t('campaign', 'Sendout cancelled.'));
    }

    /**
     * Deletes a sendout.
     */
    public function actionDelete(): ?Response
    {
        $this->requirePostRequest();

        $sendout = $this->_getSendoutFromParamId();

        if (!Campaign::$plugin->sendouts->deleteSendout($sendout)) {
            return $this->asFailure(Craft::t('campaign', 'Sendout could not be deleted.'));
        }

        // Log it
        Campaign::$plugin->log('Sendout "{title}" deleted by "{username}".', ['title' => $sendout->title]);

        return $this->asSuccess(Craft::t('campaign', 'Sendout deleted.'));
    }

    /**
     * Gets a sendout from a param ID.
     */
    private function _getSendoutFromParamId(): SendoutElement
    {
        $sendoutId = $this->request->getRequiredParam('sendoutId');
        $sendout = Campaign::$plugin->sendouts->getSendoutById($sendoutId);

        if ($sendout === null) {
            throw new NotFoundHttpException(Craft::t('campaign', 'Sendout not found.'));
        }

        return $sendout;
    }
}
