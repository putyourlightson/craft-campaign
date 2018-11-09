<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\controllers;

use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\models\MailingListTypeModel;
use putyourlightson\campaign\elements\MailingListElement;

use Craft;
use craft\web\Controller;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * MailingListTypesController
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0   
 */
class MailingListTypesController extends Controller
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
     * @param int|null                  $mailingListTypeId    The mailing list type’s ID, if editing an existing mailing list type.
     * @param MailingListTypeModel|null $mailingListType      The mailing list type being edited, if there were any validation errors.
     *
     * @return Response
     * @throws NotFoundHttpException if the requested mailing list type is not found
     */
    public function actionEditMailingListType(int $mailingListTypeId = null, MailingListTypeModel $mailingListType = null): Response
    {
        // Get the mailing list type
        // ---------------------------------------------------------------------

        if ($mailingListType === null) {
            if ($mailingListTypeId !== null) {
                $mailingListType = Campaign::$plugin->mailingListTypes->getMailingListTypeById($mailingListTypeId);

                if ($mailingListType === null) {
                    throw new NotFoundHttpException(Craft::t('campaign', 'Mailing list type not found.'));
                }
            }
            else {
                $mailingListType = new MailingListTypeModel();
            }
        }

        $variables = [
            'mailingListTypeId' => $mailingListTypeId,
            'mailingListType' => $mailingListType
        ];

        // Set the title
        // ---------------------------------------------------------------------

        if ($mailingListTypeId === null) {
            $variables['title'] = Craft::t('campaign', 'Create a new mailing list');
        } else {
            $variables['title'] = $mailingListType->name;
        }

        // Get the site options
        $variables['siteOptions'] = Campaign::$plugin->settings->getSiteOptions();

        // Full page form variables
        $variables['fullPageForm'] = true;

        // Render the template
        return $this->renderTemplate('campaign/settings/mailinglisttypes/_edit', $variables);
    }

    /**
     * @return Response|null
     * @throws \Throwable
     * @throws BadRequestHttpException
     */
    public function actionSaveMailingListType()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $mailingListTypeId = $request->getBodyParam('mailingListTypeId');

        if ($mailingListTypeId) {
            $mailingListType = Campaign::$plugin->mailingListTypes->getMailingListTypeById($mailingListTypeId);

            if ($mailingListType === null) {
                throw new NotFoundHttpException(Craft::t('campaign', 'Mailing list type not found.'));
            }
        } else {
            $mailingListType = new MailingListTypeModel();
        }

        // Set the attributes, defaulting to the existing values for whatever is missing from the post data
        $mailingListType->siteId = $request->getBodyParam('siteId', $mailingListType->siteId);
        $mailingListType->name = $request->getBodyParam('name', $mailingListType->name);
        $mailingListType->handle = $request->getBodyParam('handle', $mailingListType->handle);
        $mailingListType->doubleOptIn = (bool)$request->getBodyParam('doubleOptIn', $mailingListType->doubleOptIn);
        $mailingListType->verifyEmailSubject = $request->getBodyParam('verifyEmailSubject', $mailingListType->verifyEmailSubject);
        $mailingListType->verifyEmailTemplate = $request->getBodyParam('verifyEmailTemplate', $mailingListType->verifyEmailTemplate);
        $mailingListType->verifySuccessTemplate = $request->getBodyParam('verifySuccessTemplate', $mailingListType->verifySuccessTemplate);
        $mailingListType->subscribeSuccessTemplate = $request->getBodyParam('subscribeSuccessTemplate', $mailingListType->subscribeSuccessTemplate);
        $mailingListType->unsubscribeSuccessTemplate = $request->getBodyParam('unsubscribeSuccessTemplate', $mailingListType->unsubscribeSuccessTemplate);

        // Set the field layout
        $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();
        $fieldLayout->type = MailingListElement::class;
        $mailingListType->setFieldLayout($fieldLayout);

        // Save it
        if (!Campaign::$plugin->mailingListTypes->saveMailingListType($mailingListType)) {
            Craft::$app->getSession()->setError(Craft::t('campaign', 'Couldn’t save mailing list type.'));

            // Send the mailing list type back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'mailingListType' => $mailingListType
            ]);

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('campaign', 'Mailing list type saved.'));

        return $this->redirectToPostedUrl($mailingListType);
    }

    /**
     * Deletes a mailing list type
     *
     * @return Response
     * @throws BadRequestHttpException
     * @throws \Throwable
     */
    public function actionDeleteMailingListType(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $mailingListTypeId = Craft::$app->getRequest()->getRequiredBodyParam('id');

        Campaign::$plugin->mailingListTypes->deleteMailingListTypeById($mailingListTypeId);

        return $this->asJson(['success' => true]);
    }
}
