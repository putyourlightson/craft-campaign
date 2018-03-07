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
                    throw new NotFoundHttpException('Mailing list type not found');
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

        $mailingListTypeId = Craft::$app->getRequest()->getBodyParam('mailingListTypeId');

        if ($mailingListTypeId) {
            $mailingListType = Campaign::$plugin->mailingListTypes->getMailingListTypeById($mailingListTypeId);

            if ($mailingListType === null) {
                throw new NotFoundHttpException('Mailing list type not found');
            }
        } else {
            $mailingListType = new MailingListTypeModel();
        }

        // Set the simple stuff
        $mailingListType->name = Craft::$app->getRequest()->getBodyParam('name', $mailingListType->name);
        $mailingListType->handle = Craft::$app->getRequest()->getBodyParam('handle', $mailingListType->handle);
        $mailingListType->doubleOptIn = (bool)Craft::$app->getRequest()->getBodyParam('doubleOptIn', $mailingListType->doubleOptIn);
        $mailingListType->requireMlid = (bool)Craft::$app->getRequest()->getBodyParam('requireMlid', $mailingListType->requireMlid);

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
