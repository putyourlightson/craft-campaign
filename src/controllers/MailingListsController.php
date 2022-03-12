<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\controllers;

use Craft;
use craft\helpers\DateTimeHelper;

use craft\web\Controller;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\MailingListElement;
use Throwable;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

class MailingListsController extends Controller
{
    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        // Require permission
        $this->requirePermission('campaign:mailingLists');

        return parent::beforeAction($action);
    }

    /**
     * Main edit page.
     *
     * @param string $mailingListTypeHandle The mailing list type’s handle
     * @param int|null $mailingListId The mailing list’s ID, if editing an existing mailingList.
     * @param MailingListElement|null $mailingList The mailing list being edited, if there were any validation errors.
     */
    public function actionEditMailingList(string $mailingListTypeHandle, int $mailingListId = null, MailingListElement $mailingList = null): Response
    {
        $variables = [];

        // Get the mailing list type
        // ---------------------------------------------------------------------

        $mailingListType = Campaign::$plugin->mailingListTypes->getMailingListTypeByHandle($mailingListTypeHandle);

        if ($mailingListType === null) {
            throw new NotFoundHttpException(Craft::t('campaign', 'Mailing list type not found.'));
        }

        // Set the current site
        Craft::$app->getSites()->setCurrentSite($mailingListType->siteId);

        // Get the mailing list
        // ---------------------------------------------------------------------

        if ($mailingList === null) {
            if ($mailingListId !== null) {
                $mailingList = Campaign::$plugin->mailingLists->getMailingListById($mailingListId);

                if ($mailingList === null) {
                    throw new NotFoundHttpException(Craft::t('campaign', 'Mailing list not found.'));
                }
            }
            else {
                $mailingList = new MailingListElement();
                $mailingList->mailingListTypeId = $mailingListType->id;
                $mailingList->enabled = true;
            }
        }

        $mailingList->fieldLayoutId = $mailingListType->fieldLayoutId;


        // Set the variables
        // ---------------------------------------------------------------------

        $variables['mailingListTypeHandle'] = $mailingListTypeHandle;
        $variables['mailingListId'] = $mailingListId;
        $variables['mailingList'] = $mailingList;
        $variables['mailingListType'] = $mailingListType;

        // Set the title and slug
        // ---------------------------------------------------------------------

        if ($mailingListId === null) {
            $variables['title'] = Craft::t('campaign', 'Create a new mailing list');
        }
        else {
            $variables['title'] = $mailingList->title;
            $variables['slug'] = $mailingList->slug;
        }

        // Add fields from first field layout tab
        $fieldLayoutTabs = $mailingListType->getFieldLayout()->getTabs();
        $fieldLayoutTab = $fieldLayoutTabs[0] ?? null;
        $variables['fields'] = $fieldLayoutTab !== null ? $fieldLayoutTab->getFields() : [];

        // Get the settings
        $variables['settings'] = Campaign::$plugin->getSettings();

        // Full page form variables
        $variables['fullPageForm'] = true;
        $variables['continueEditingUrl'] = 'campaign/mailinglists/' . $mailingListTypeHandle . '/{id}';
        $variables['saveShortcutRedirect'] = $variables['continueEditingUrl'];

        // Render the template
        return $this->renderTemplate('campaign/mailinglists/_edit', $variables);
    }

    /**
     * Saves a mailing list.
     */
    public function actionSaveMailingList(): ?Response
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $mailingListId = $request->getBodyParam('mailingListId');

        if ($mailingListId) {
            $mailingList = Campaign::$plugin->mailingLists->getMailingListById($mailingListId);

            if ($mailingList === null) {
                throw new NotFoundHttpException(Craft::t('campaign', 'Mailing list not found.'));
            }
        }
        else {
            $mailingList = new MailingListElement();
            $mailingList->mailingListTypeId = $request->getRequiredBodyParam('mailingListTypeId');
        }

        // If this mailing list should be duplicated then swap it for a duplicate
        if ($request->getBodyParam('duplicate')) {
            try {
                /** @var MailingListElement $mailingList */
                $mailingList = Craft::$app->getElements()->duplicateElement($mailingList);
            }
            catch (Throwable $e) {
                throw new ServerErrorHttpException(Craft::t('campaign', 'An error occurred when duplicating the mailing list.'), 0, $e);
            }
        }

        $mailingList->title = $request->getBodyParam('title', $mailingList->title);
        $mailingList->slug = $request->getBodyParam('slug', $mailingList->slug);

        // Set the attributes, defaulting to the existing values for whatever is missing from the post data
        $mailingList->enabled = (bool)$request->getBodyParam('enabled', $mailingList->enabled);

        // Set the site ID
        $mailingList->siteId = $mailingList->getMailingListType()->siteId;

        // Set the field layout ID
        $mailingList->fieldLayoutId = $mailingList->getMailingListType()->fieldLayoutId;

        // Set the field locations
        $fieldsLocation = $request->getParam('fieldsLocation', 'fields');
        $mailingList->setFieldValuesFromRequest($fieldsLocation);

        // Save it
        if (!Craft::$app->getElements()->saveElement($mailingList)) {
            if ($request->getAcceptsJson()) {
                return $this->asJson([
                    'errors' => $mailingList->getErrors(),
                ]);
            }

            Craft::$app->getSession()->setError(Craft::t('campaign', 'Couldn’t save mailing list.'));

            // Send the mailingList back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'mailingList' => $mailingList,
            ]);

            return null;
        }

        if ($request->getAcceptsJson()) {
            $return = [];

            $return['success'] = true;
            $return['id'] = $mailingList->id;
            $return['title'] = $mailingList->title;

            if (!$request->getIsConsoleRequest() && $request->getIsCpRequest()) {
                $return['cpEditUrl'] = $mailingList->getCpEditUrl();
            }

            $return['dateCreated'] = DateTimeHelper::toIso8601($mailingList->dateCreated);
            $return['dateUpdated'] = DateTimeHelper::toIso8601($mailingList->dateUpdated);

            return $this->asJson($return);
        }

        Craft::$app->getSession()->setNotice(Craft::t('campaign', 'Mailing list saved.'));

        return $this->redirectToPostedUrl($mailingList);
    }

    /**
     * Deletes a mailing list.
     */
    public function actionDeleteMailingList(): ?Response
    {
        $this->requirePostRequest();

        $mailingListId = Craft::$app->getRequest()->getRequiredBodyParam('mailingListId');
        $mailingList = Campaign::$plugin->mailingLists->getMailingListById($mailingListId);

        if ($mailingList === null) {
            throw new NotFoundHttpException(Craft::t('campaign', 'Mailing list not found.'));
        }

        if (!Craft::$app->getElements()->deleteElement($mailingList)) {
            if (Craft::$app->getRequest()->getAcceptsJson()) {
                return $this->asJson(['success' => false]);
            }

            Craft::$app->getSession()->setError(Craft::t('campaign', 'Couldn’t delete mailing list.'));

            // Send the mailing list back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'mailingList' => $mailingList,
            ]);

            return null;
        }

        if (Craft::$app->getRequest()->getAcceptsJson()) {
            return $this->asJson(['success' => true]);
        }

        Craft::$app->getSession()->setNotice(Craft::t('campaign', 'Mailing list deleted.'));

        return $this->redirectToPostedUrl($mailingList);
    }
}
