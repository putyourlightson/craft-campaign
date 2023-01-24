<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\controllers;

use Craft;
use putyourlightson\campaign\base\BaseSettingsController;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\helpers\SettingsHelper;
use putyourlightson\campaign\models\MailingListTypeModel;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class MailingListTypesController extends BaseSettingsController
{
    /**
     * Main edit page.
     *
     * @param int|null $mailingListTypeId The mailing list type’s ID, if editing an existing mailing list type.
     * @param MailingListTypeModel|null $mailingListType The mailing list type being edited, if there were any validation errors.
     */
    public function actionEdit(int $mailingListTypeId = null, MailingListTypeModel $mailingListType = null): Response
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
        $variables['siteOptions'] = SettingsHelper::getSiteOptions();

        // Full page form variables
        $variables['fullPageForm'] = true;

        // Render the template
        return $this->renderTemplate('campaign/settings/mailinglisttypes/_edit', $variables);
    }

    /**
     * Saves a mailing list type.
     */
    public function actionSave(): ?Response
    {
        $this->requirePostRequest();

        $mailingListTypeId = $this->request->getBodyParam('mailingListTypeId');

        if ($mailingListTypeId) {
            $mailingListType = Campaign::$plugin->mailingListTypes->getMailingListTypeById($mailingListTypeId);

            if ($mailingListType === null) {
                throw new NotFoundHttpException(Craft::t('campaign', 'Mailing list type not found.'));
            }
        } else {
            $mailingListType = new MailingListTypeModel();
        }

        // Set the attributes, defaulting to the existing values for whatever is missing from the post data
        $mailingListType->siteId = $this->request->getBodyParam('siteId', $mailingListType->siteId);
        $mailingListType->name = $this->request->getBodyParam('name', $mailingListType->name);
        $mailingListType->handle = $this->request->getBodyParam('handle', $mailingListType->handle);
        $mailingListType->subscribeVerificationRequired = (bool)$this->request->getBodyParam('subscribeVerificationRequired', $mailingListType->subscribeVerificationRequired);
        $mailingListType->subscribeVerificationEmailSubject = $this->request->getBodyParam('subscribeVerificationEmailSubject', $mailingListType->subscribeVerificationEmailSubject);
        $mailingListType->subscribeVerificationEmailTemplate = $this->request->getBodyParam('subscribeVerificationEmailTemplate', $mailingListType->subscribeVerificationEmailTemplate);
        $mailingListType->subscribeSuccessTemplate = $this->request->getBodyParam('subscribeSuccessTemplate', $mailingListType->subscribeSuccessTemplate);
        $mailingListType->unsubscribeFormAllowed = (bool)$this->request->getBodyParam('unsubscribeFormAllowed', $mailingListType->unsubscribeFormAllowed);
        $mailingListType->unsubscribeVerificationEmailSubject = $this->request->getBodyParam('unsubscribeVerificationEmailSubject', $mailingListType->unsubscribeVerificationEmailSubject);
        $mailingListType->unsubscribeVerificationEmailTemplate = $this->request->getBodyParam('unsubscribeVerificationEmailTemplate', $mailingListType->unsubscribeVerificationEmailTemplate);
        $mailingListType->unsubscribeSuccessTemplate = $this->request->getBodyParam('unsubscribeSuccessTemplate', $mailingListType->unsubscribeSuccessTemplate);

        // Set the field layout
        $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();
        $fieldLayout->type = MailingListElement::class;
        $mailingListType->setFieldLayout($fieldLayout);

        // Save it
        if (!Campaign::$plugin->mailingListTypes->saveMailingListType($mailingListType)) {
            return $this->asModelFailure($mailingListType, Craft::t('campaign', 'Couldn’t save mailing list type.'), 'mailingListType');
        }

        return $this->asModelSuccess($mailingListType, Craft::t('campaign', 'Mailing list type saved.'), 'mailingListType');
    }

    /**
     * Deletes a mailing list type.
     */
    public function actionDelete(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $mailingListTypeId = $this->request->getRequiredBodyParam('id');
        Campaign::$plugin->mailingListTypes->deleteMailingListTypeById($mailingListTypeId);

        return $this->asSuccess();
    }
}
