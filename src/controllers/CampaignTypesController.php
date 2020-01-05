<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\controllers;

use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\models\CampaignTypeModel;
use putyourlightson\campaign\elements\CampaignElement;

use Craft;
use craft\web\Controller;
use Throwable;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * CampaignTypesController
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 */
class CampaignTypesController extends Controller
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
        $this->requirePermission('campaign:settings');
    }

    /**
     * @param int|null $campaignTypeId The campaign type’s ID, if editing an existing campaign type.
     * @param CampaignTypeModel|null $campaignType The campaign type being edited, if there were any validation errors.
     *
     * @return Response
     * @throws NotFoundHttpException
     */
    public function actionEditCampaignType(int $campaignTypeId = null, CampaignTypeModel $campaignType = null): Response
    {
        // Get the campaign type
        // ---------------------------------------------------------------------

        if ($campaignType === null) {
            if ($campaignTypeId !== null) {
                $campaignType = Campaign::$plugin->campaignTypes->getCampaignTypeById($campaignTypeId);

                if ($campaignType === null) {
                    throw new NotFoundHttpException(Craft::t('campaign', 'Campaign type not found.'));
                }
            }
            else {
                $campaignType = new CampaignTypeModel();
            }
        }

        // Set the variables
        // ---------------------------------------------------------------------

        $variables = [
            'campaignTypeId' => $campaignTypeId,
            'campaignType' => $campaignType
        ];

        // Set the title
        // ---------------------------------------------------------------------

        if ($campaignTypeId === null) {
            $variables['title'] = Craft::t('campaign', 'Create a new campaign');
        }
        else {
            $variables['title'] = $campaignType->name;
        }

        // Get the site options
        $variables['siteOptions'] = Campaign::$plugin->settings->getSiteOptions();

        // Full page form variables
        $variables['fullPageForm'] = true;

        // Render the template
        return $this->renderTemplate('campaign/settings/campaigntypes/_edit', $variables);
    }

    /**
     * @return Response|null
     * @throws Throwable
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     */
    public function actionSaveCampaignType()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $campaignTypeId = $request->getBodyParam('campaignTypeId');

        if ($campaignTypeId) {
            $campaignType = Campaign::$plugin->campaignTypes->getCampaignTypeById($campaignTypeId);

            if (!$campaignType) {
                throw new NotFoundHttpException(Craft::t('campaign', 'Campaign type not found.'));
            }
        }
        else {
            $campaignType = new CampaignTypeModel();
        }

        // Set the attributes, defaulting to the existing values for whatever is missing from the post data
        $campaignType->siteId = $request->getBodyParam('siteId', $campaignType->siteId);
        $campaignType->name = $request->getBodyParam('name', $campaignType->name);
        $campaignType->handle = $request->getBodyParam('handle', $campaignType->handle);
        $campaignType->uriFormat = $request->getBodyParam('uriFormat', $campaignType->uriFormat);
        $campaignType->htmlTemplate = $request->getBodyParam('htmlTemplate', $campaignType->htmlTemplate);
        $campaignType->plaintextTemplate = $request->getBodyParam('plaintextTemplate', $campaignType->plaintextTemplate);
        $campaignType->queryStringParameters = $request->getBodyParam('queryStringParameters', $campaignType->queryStringParameters);

        // Set the field layout
        $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();
        $fieldLayout->type = CampaignElement::class;
        $campaignType->setFieldLayout($fieldLayout);

        // Save it
        if (!Campaign::$plugin->campaignTypes->saveCampaignType($campaignType)) {
            Craft::$app->getSession()->setError(Craft::t('campaign', 'Couldn’t save campaign type.'));

            // Send the campaign type back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'campaignType' => $campaignType
            ]);

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('campaign', 'Campaign type saved.'));

        return $this->redirectToPostedUrl($campaignType);
    }

    /**
     * Deletes a campaign type
     *
     * @return Response
     * @throws BadRequestHttpException
     * @throws Throwable
     */
    public function actionDeleteCampaignType(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $campaignTypeId = Craft::$app->getRequest()->getRequiredBodyParam('id');

        Campaign::$plugin->campaignTypes->deleteCampaignTypeById($campaignTypeId);

        return $this->asJson(['success' => true]);
    }
}
