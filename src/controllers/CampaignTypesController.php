<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\controllers;

use Craft;
use craft\web\Controller;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\CampaignElement;

use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\models\CampaignTypeModel;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class CampaignTypesController extends Controller
{
    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        // Require permission
        $this->requirePermission('campaign:settings');

        return parent::beforeAction($action);
    }

    /**
     * Main edit page.
     */
    public function actionEdit(int $campaignTypeId = null, CampaignTypeModel $campaignType = null): Response
    {
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

        $variables = [
            'campaignTypeId' => $campaignTypeId,
            'campaignType' => $campaignType,
            'siteOptions' => Campaign::$plugin->settingsService->getSiteOptions(),
            'contactElementType' => ContactElement::class,
            'fullPageForm' => true,
        ];

        if ($campaignTypeId === null) {
            $variables['title'] = Craft::t('campaign', 'Create a new campaign');
        }
        else {
            $variables['title'] = $campaignType->name;
        }

        return $this->renderTemplate('campaign/settings/campaigntypes/_edit', $variables);
    }

    /**
     * Saves the campaign type.
     */
    public function actionSave(): ?Response
    {
        $this->requirePostRequest();

        $campaignTypeId = $this->request->getBodyParam('campaignTypeId');

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
        $campaignType->siteId = $this->request->getBodyParam('siteId', $campaignType->siteId);
        $campaignType->name = $this->request->getBodyParam('name', $campaignType->name);
        $campaignType->handle = $this->request->getBodyParam('handle', $campaignType->handle);
        $campaignType->uriFormat = $this->request->getBodyParam('uriFormat', $campaignType->uriFormat);
        $campaignType->htmlTemplate = $this->request->getBodyParam('htmlTemplate', $campaignType->htmlTemplate);
        $campaignType->plaintextTemplate = $this->request->getBodyParam('plaintextTemplate', $campaignType->plaintextTemplate);
        $campaignType->queryStringParameters = $this->request->getBodyParam('queryStringParameters', $campaignType->queryStringParameters);
        $campaignType->testContactIds = $this->request->getBodyParam('testContactIds', $campaignType->testContactIds);

        // Set the field layout
        $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();
        $fieldLayout->type = CampaignElement::class;
        $campaignType->setFieldLayout($fieldLayout);

        // Save it
        if (!Campaign::$plugin->campaignTypes->saveCampaignType($campaignType)) {
            return $this->asModelFailure($campaignType, Craft::t('campaign', 'Couldn’t save campaign type.'), 'campaignType');
        }

        return $this->asModelSuccess($campaignType, Craft::t('campaign', 'Campaign type saved.'), 'campaignType');
    }

    /**
     * Deletes a campaign type.
     */
    public function actionDelete(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $campaignTypeId = $this->request->getRequiredBodyParam('id');
        Campaign::$plugin->campaignTypes->deleteCampaignTypeById($campaignTypeId);

        return $this->asSuccess();
    }
}
