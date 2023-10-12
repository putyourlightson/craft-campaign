<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\controllers;

use Craft;
use craft\base\Element;
use craft\controllers\CategoriesController;
use craft\errors\SiteNotFoundException;
use craft\helpers\Cp;
use craft\helpers\ElementHelper;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use DateTime;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\CampaignElement;
use putyourlightson\campaign\records\ContactCampaignRecord;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class CampaignsController extends Controller
{
    /**
     * Creates a new unpublished draft and redirects to its edit page.
     *
     * @see CategoriesController::actionCreate()
     * @since 2.0.0
     */
    public function actionCreate(string $campaignTypeHandle): Response
    {
        $campaignType = Campaign::$plugin->campaignTypes->getCampaignTypeByHandle($campaignTypeHandle);
        if (!$campaignType) {
            throw new BadRequestHttpException("Invalid campaign type handle: $campaignTypeHandle");
        }

        $site = Cp::requestedSite();
        if (!$site) {
            throw new SiteNotFoundException();
        }

        // Create & populate the draft
        $campaign = Craft::createObject(CampaignElement::class);
        $campaign->siteId = $site->id;
        $campaign->campaignTypeId = $campaignType->id;

        // Make sure the user is allowed to create this campaign
        $user = Craft::$app->getUser()->getIdentity();
        if (!$campaign->canSave($user)) {
            throw new ForbiddenHttpException('User not authorized to save this campaign.');
        }

        // Title & slug
        $campaign->title = $this->request->getQueryParam('title');
        $campaign->slug = $this->request->getQueryParam('slug');
        if ($campaign->title && !$campaign->slug) {
            $campaign->slug = ElementHelper::generateSlug($campaign->title, null, $site->language);
        }
        if (!$campaign->slug) {
            $campaign->slug = ElementHelper::tempSlug();
        }

        // Save it
        $campaign->setScenario(Element::SCENARIO_ESSENTIALS);
        if (!Craft::$app->getDrafts()->saveElementAsDraft($campaign, Craft::$app->getUser()->getId(), null, null, false)) {
            return $this->asModelFailure($campaign, Craft::t('app', 'Couldn’t create {type}.', [
                'type' => CampaignElement::lowerDisplayName(),
            ]), 'campaign');
        }

        $editUrl = $campaign->getCpEditUrl();

        $response = $this->asModelSuccess($campaign, Craft::t('app', '{type} created.', [
            'type' => CampaignElement::displayName(),
        ]), 'campaign', array_filter([
            'cpEditUrl' => $this->request->isCpRequest ? $editUrl : null,
        ]));

        if (!$this->request->getAcceptsJson()) {
            $response->redirect(UrlHelper::urlWithParams($editUrl, [
                'fresh' => 1,
            ]));
        }

        return $response;
    }

    /**
     * Sends a test
     */
    public function actionSendTest(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $campaignId = $this->request->getRequiredBodyParam('campaignId');

        // Use the elements service since it might be a draft.
        /** @var CampaignElement|null $campaign */
        $campaign = Craft::$app->getElements()->getElementById($campaignId, null, '*');

        if ($campaign === null) {
            throw new NotFoundHttpException(Craft::t('campaign', 'Campaign not found.'));
        }

        $contactIds = $this->request->getBodyParam('contactIds');

        if (empty($contactIds)) {
            return $this->asFailure(Craft::t('campaign', 'At least one contact must be selected.'));
        }

        $contacts = Campaign::$plugin->contacts->getContactsByIds($contactIds);

        foreach ($contacts as $contact) {
            if (!Campaign::$plugin->campaigns->sendTest($campaign, $contact)) {
                return $this->asFailure(Craft::t('campaign', 'Couldn’t send test email.'));
            }
        }

        return $this->asSuccess(Craft::t('campaign', 'Test email sent.'));
    }

    /**
     * Closes a campaign.
     */
    public function actionClose(): ?Response
    {
        $this->requirePostRequest();

        $campaignId = $this->request->getRequiredBodyParam('campaignId');
        $campaign = Campaign::$plugin->campaigns->getCampaignById($campaignId);

        if (!$campaign) {
            throw new NotFoundHttpException(Craft::t('campaign', 'Campaign not found.'));
        }

        // Set closed date to now
        $campaign->dateClosed = new DateTime();

        if (!Craft::$app->getElements()->saveElement($campaign, false)) {
            return $this->asModelFailure($campaign, Craft::t('campaign', 'Couldn’t close campaign.'), 'campaign', [
                'errors' => $campaign->getErrors(),
            ]);
        }

        // Delete all contact activity for this campaign
        ContactCampaignRecord::deleteAll(['campaignId' => $campaign->id]);

        return $this->asModelSuccess($campaign, Craft::t('campaign', 'Campaign closed.'), 'campaign');
    }
}
