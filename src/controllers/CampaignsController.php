<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\controllers;

use DateTime;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\CampaignElement;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\records\ContactCampaignRecord;

use Craft;
use craft\helpers\DateTimeHelper;
use craft\web\Controller;
use craft\helpers\Json;
use Throwable;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

/**
 * CampaignsController
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 */
class CampaignsController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     * @throws ForbiddenHttpException
     */
    public function init()
    {
        // Require permission
        $this->requirePermission('campaign:campaigns');
    }

    /**
     * @param string               $campaignTypeHandle The campaign type’s handle
     * @param int|null             $campaignId         The campaign’s ID, if editing an existing campaign.
     * @param CampaignElement|null $campaign           The campaign being edited, if there were any validation errors.
     *
     * @return Response
     * @throws NotFoundHttpException if the campaign or campaign type handle is not found
     * @throws InvalidConfigException
     */
    public function actionEditCampaign(string $campaignTypeHandle, int $campaignId = null, CampaignElement $campaign = null): Response
    {
        $request = Craft::$app->getRequest();

        $variables = [];

        // Get the campaign type
        // ---------------------------------------------------------------------

        $campaignType = Campaign::$plugin->campaignTypes->getCampaignTypeByHandle($campaignTypeHandle);

        if ($campaignType === null) {
            throw new NotFoundHttpException(Craft::t('campaign', 'Campaign type not found.'));
        }

        // Get the campaign
        // ---------------------------------------------------------------------

        if ($campaign === null) {
            if ($campaignId !== null) {
                $campaign = Campaign::$plugin->campaigns->getCampaignById($campaignId);

                if ($campaign === null) {
                    throw new NotFoundHttpException(Craft::t('campaign', 'Campaign not found.'));
                }
            }
            else {
                $campaign = new CampaignElement();
                $campaign->campaignTypeId = $campaignType->id;
            }
        }

        $campaign->fieldLayoutId = $campaignType->fieldLayoutId;

        // Set the current site
        Craft::$app->getSites()->setCurrentSite($campaignType->siteId);

        // Set the variables
        // ---------------------------------------------------------------------

        $variables['campaignTypeHandle'] = $campaignTypeHandle;
        $variables['campaignId'] = $campaignId;
        $variables['campaign'] = $campaign;
        $variables['campaignType'] = $campaignType;

        // Set the title
        // ---------------------------------------------------------------------

        if ($campaignId === null) {
            $variables['title'] = Craft::t('campaign', 'Create a new campaign');
        } else {
            $variables['title'] = $campaign->title;
        }

        // Get fields from first field layout tab
        $fieldLayoutTabs = $campaignType->getFieldLayout()->getTabs();
        $fieldLayoutTab = isset($fieldLayoutTabs[0]) ? $fieldLayoutTabs[0] : null;
        $variables['fields'] = $fieldLayoutTab !== null ? $fieldLayoutTab->getFields() : [];

        // Enable live preview?
        if (!$request->isMobileBrowser(true) && $campaignType->hasValidTemplates()) {
            $this->getView()->registerJs('Craft.LivePreview.init('.Json::encode([
                'fields' => '#title-field, #fields > div > div > .field',
                'extraFields' => '#settings',
                'previewUrl' => $campaign->getUrl(),
                'previewAction' => Craft::$app->security->hashData('campaign/campaigns/preview-campaign'),
                'previewParams' => [
                    'campaignId' => $campaign->id,
                    'campaignTypeId' => $campaignType->id,
                    'siteId' => $campaignType->siteId,
                ]
            ]).');');

            $variables['showPreviewBtn'] = true;

            // Set share URL
            if ($campaign->id !== null && $campaign->enabled) {
                $variables['shareUrl'] = $campaign->getUrl();
            }
        }
        else {
            $variables['showPreviewBtn'] = false;
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

        switch ($campaign->getStatus()) {
            case CampaignElement::STATUS_SENT:
                $variables['actions'][0][] = [
                    'action' => 'campaign/campaigns/close-campaign',
                    'destructive' => 'true',
                    'label' => Craft::t('campaign', 'Close this campaign…'),
                    'confirm' => Craft::t('campaign', 'Are you sure you want to close this campaign? This will remove all contact activity related to this campaign. This action cannot be undone.')
                ];
                break;
        }

        $variables['actions'][0][] = [
            'action' => 'campaign/campaigns/delete-campaign',
            'destructive' => 'true',
            'redirect' => 'campaign/campaigns',
            'label' => Craft::t('app', 'Delete…'),
            'confirm' => Craft::t('campaign', 'Are you sure you want to delete this campaign? This will also delete all reports and contact activity related to this campaign.')
        ];

        // Full page form variables
        $variables['fullPageForm'] = true;
        $variables['continueEditingUrl'] = 'campaign/campaigns/'.$campaignTypeHandle.'/{id}';
        $variables['saveShortcutRedirect'] = $variables['continueEditingUrl'];

        // Render the template
        return $this->renderTemplate('campaign/campaigns/_edit', $variables);
    }

    /**
     * Previews a campaign.
     *
     * @return Response
     * @throws NotFoundHttpException
     * @throws InvalidConfigException
     * @throws BadRequestHttpException
     */
    public function actionPreviewCampaign(): Response
    {
        $this->requirePostRequest();

        $campaign = $this->_getCampaign();

        $this->_populateCampaign($campaign);

        // Have this campaign override any freshly queried campaigns with the same ID/site ID
        if ($campaign->id !== null) {
            Craft::$app->getElements()->setPlaceholderElement($campaign);
        }

        $this->getView()->getTwig()->disableStrictVariables();

        return $this->renderTemplate($campaign->getCampaignType()->htmlTemplate, [
            'campaign' => $campaign
        ]);
    }

    /**
     * Sends a test
     *
     * @return Response
     * @throws BadRequestHttpException
     * @throws Exception
     * @throws NotFoundHttpException
     */
    public function actionSendTest(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $contactId = Craft::$app->getRequest()->getBodyParam('contactId');
        $campaign = $this->_getCampaign();

        // Validate test contact
        if (empty($contactId)) {
            return $this->asJson(['success' => false, 'error' => Craft::t('campaign', 'A contact must be submitted.')]);
        }

        $contact = Campaign::$plugin->contacts->getContactById($contactId);

        if ($contact === null) {
            return $this->asJson(['success' => false, 'error' => Craft::t('campaign', 'Contact not found.')]);
        }

        if (!Campaign::$plugin->campaigns->sendTest($campaign, $contact)) {
            return $this->asJson(['success' => false, 'error' => Craft::t('campaign', 'Couldn’t send test email.')]);
        }

        return $this->asJson(['success' => true]);
    }

    /**
     * @return Response|null
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException
     * @throws Exception
     */
    public function actionSaveCampaign()
    {
        $this->requirePostRequest();

        $campaign = $this->_getCampaign();
        $request = Craft::$app->getRequest();

        // If this campaign should be duplicated then swap it for a duplicate
        if ((bool)$request->getBodyParam('duplicate')) {
            try {
                $campaign = Craft::$app->getElements()->duplicateElement($campaign);
            }
            catch (Throwable $e) {
                throw new ServerErrorHttpException(Craft::t('campaign', 'An error occurred when duplicating the campaign.'), 0, $e);
            }

            // Reset the stats
            /** @var CampaignElement $campaign */
            $campaign->setAttributes([
                'recipients' => 0,
                'opened' => 0,
                'clicked' => 0,
                'opens' => 0,
                'clicks' => 0,
                'unsubscribed' => 0,
                'complained' => 0,
                'bounced' => 0,
                'dateClosed' => null,
            ]);
        }

        $this->_populateCampaign($campaign);

        // Save it
        if (!Craft::$app->getElements()->saveElement($campaign)) {
            if ($request->getAcceptsJson()) {
                return $this->asJson([
                    'errors' => $campaign->getErrors(),
                ]);
            }

            Craft::$app->getSession()->setError(Craft::t('campaign', 'Couldn’t save campaign.'));

            // Send the campaign back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'campaign' => $campaign
            ]);

            return null;
        }

        if ($request->getAcceptsJson()) {
            $return = [];

            $return['success'] = true;
            $return['id'] = $campaign->id;
            $return['title'] = $campaign->title;

            if (!$request->getIsConsoleRequest() AND $request->getIsCpRequest()) {
                $return['cpEditUrl'] = $campaign->getCpEditUrl();
            }

            $return['dateCreated'] = DateTimeHelper::toIso8601($campaign->dateCreated);
            $return['dateUpdated'] = DateTimeHelper::toIso8601($campaign->dateUpdated);

            return $this->asJson($return);
        }

        Craft::$app->getSession()->setNotice(Craft::t('campaign', 'Campaign saved.'));

        return $this->redirectToPostedUrl($campaign);
    }

    /**
     * Closes a campaign
     *
     * @return Response|null
     * @throws BadRequestHttpException
     * @throws Exception
     * @throws NotFoundHttpException
     */
    public function actionCloseCampaign()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $campaignId = $request->getRequiredBodyParam('campaignId');
        $campaign = Campaign::$plugin->campaigns->getCampaignById($campaignId);

        if (!$campaign) {
            throw new NotFoundHttpException(Craft::t('campaign', 'Campaign not found.'));
        }

        // Set closed date to now
        $campaign->dateClosed = new DateTime();

        if (!Craft::$app->getElements()->saveElement($campaign)) {
            if ($request->getAcceptsJson()) {
                return $this->asJson([
                    'errors' => $campaign->getErrors(),
                ]);
            }

            Craft::$app->getSession()->setError(Craft::t('campaign', 'Couldn’t close campaign.'));

            // Send the campaign back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'campaign' => $campaign
            ]);

            return null;
        }

        // Delete all contact activity for this campaign
        ContactCampaignRecord::deleteAll(['campaignId' => $campaign->id]);

        if ($request->getAcceptsJson()) {
            return $this->asJson(['success' => true]);
        }

        Craft::$app->getSession()->setNotice(Craft::t('campaign', 'Campaign closed.'));

        return $this->redirectToPostedUrl($campaign);
    }

    /**
     * Deletes a campaign
     *
     * @return Response|null
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     */
    public function actionDeleteCampaign()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $campaignId = $request->getRequiredBodyParam('campaignId');
        $campaign = Campaign::$plugin->campaigns->getCampaignById($campaignId);

        if (!$campaign) {
            throw new NotFoundHttpException(Craft::t('campaign', 'Campaign not found.'));
        }

        if (!Craft::$app->getElements()->deleteElement($campaign)) {
            if ($request->getAcceptsJson()) {
                return $this->asJson(['success' => false]);
            }

            Craft::$app->getSession()->setError(Craft::t('campaign', 'Couldn’t delete campaign.'));

            // Send the campaign back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'campaign' => $campaign
            ]);

            return null;
        }

        if ($request->getAcceptsJson()) {
            return $this->asJson(['success' => true]);
        }

        Craft::$app->getSession()->setNotice(Craft::t('campaign', 'Campaign deleted.'));

        return $this->redirectToPostedUrl($campaign);
    }

    // Private Methods
    // =========================================================================

    /**
     * Gets a campaign or creates one if none supplied.
     *
     * @return CampaignElement
     * @throws NotFoundHttpException
     * @throws BadRequestHttpException
     */
    private function _getCampaign(): CampaignElement
    {
        $request = Craft::$app->getRequest();

        $campaignId = $request->getBodyParam('campaignId');

        if ($campaignId) {
            $campaign = Campaign::$plugin->campaigns->getCampaignById($campaignId);

            if (!$campaign) {
                throw new NotFoundHttpException(Craft::t('campaign', 'Campaign not found.'));
            }
        }
        else {
            $campaign = new CampaignElement();
            $campaign->campaignTypeId = $request->getRequiredBodyParam('campaignTypeId');
        }

        return $campaign;
    }

    /**
     * Populates a campaign with post data.
     *
     * @param CampaignElement $campaign
     *
     * @return void
     * @throws InvalidConfigException
     */
    private function _populateCampaign(CampaignElement $campaign)
    {
        $request = Craft::$app->getRequest();

        // Set the title, slug, enabled
        $campaign->title = $request->getBodyParam('title', $campaign->title);
        $campaign->slug = $request->getBodyParam('slug', $campaign->slug);
        $campaign->enabled = (bool)$request->getBodyParam('enabled', $campaign->enabled);

        // Set the site ID
        $campaign->siteId = $campaign->getCampaignType()->siteId;

        // Set the field layout ID
        $campaign->fieldLayoutId = $campaign->getCampaignType()->fieldLayoutId;

        // Set the field locations
        $fieldsLocation = $request->getParam('fieldsLocation', 'fields');
        $campaign->setFieldValuesFromRequest($fieldsLocation);
    }
}
