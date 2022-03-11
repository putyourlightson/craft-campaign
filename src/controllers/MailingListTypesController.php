<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\controllers;

use Craft;
use craft\web\Controller;
use putyourlightson\campaign\Campaign;

use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\models\MailingListTypeModel;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class MailingListTypesController extends Controller
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
     *
     * @param int|null $mailingListTypeId The mailing list type’s ID, if editing an existing mailing list type.
     * @param MailingListTypeModel|null $mailingListType The mailing list type being edited, if there were any validation errors.
     */
    public function actionEditMailingListType(int $mailingListTypeId = null, MailingListTypeModel $mailingListType = null): Response
    {
        // Get the mailing list type
        if ($mailingListType === null) {
            if ($mailingListTypeId !== null) {
                $mailingListType = Campaign::$plugin->mailingListTypes->getMailingListTypeById($mailingListTypeId);

                if ($mailingListType === null) {
                    throw new NotFoundHttpException(Craft::t('campaign', 'Mailing list type not found.'));
                }
            } else {
                $mailingListType = new MailingListTypeModel();
            }
        }

        $variables = [
            'mailingListTypeId' => $mailingListTypeId,
            'mailingListType' => $mailingListType,
        ];

        // Set the title
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
     * Saves a mailing list type.
     */
    public function actionSaveMailingListType(): ?Response
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
        $mailingListType->subscribeVerificationRequired = (bool)$request->getBodyParam('subscribeVerificationRequired', $mailingListType->subscribeVerificationRequired);
        $mailingListType->subscribeVerificationEmailSubject = $request->getBodyParam('subscribeVerificationEmailSubject', $mailingListType->subscribeVerificationEmailSubject);
        $mailingListType->subscribeVerificationEmailTemplate = $request->getBodyParam('subscribeVerificationEmailTemplate', $mailingListType->subscribeVerificationEmailTemplate);
        $mailingListType->subscribeVerificationSuccessTemplate = $request->getBodyParam('subscribeVerificationSuccessTemplate', $mailingListType->subscribeVerificationSuccessTemplate);
        $mailingListType->subscribeSuccessTemplate = $request->getBodyParam('subscribeSuccessTemplate', $mailingListType->subscribeSuccessTemplate);
        $mailingListType->unsubscribeFormAllowed = (bool)$request->getBodyParam('unsubscribeFormAllowed', $mailingListType->unsubscribeFormAllowed);
        $mailingListType->unsubscribeVerificationEmailSubject = $request->getBodyParam('unsubscribeVerificationEmailSubject', $mailingListType->unsubscribeVerificationEmailSubject);
        $mailingListType->unsubscribeVerificationEmailTemplate = $request->getBodyParam('unsubscribeVerificationEmailTemplate', $mailingListType->unsubscribeVerificationEmailTemplate);
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
                'mailingListType' => $mailingListType,
            ]);

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('campaign', 'Mailing list type saved.'));

        return $this->redirectToPostedUrl($mailingListType);
    }

    /**
     * Deletes a mailing list type.
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
