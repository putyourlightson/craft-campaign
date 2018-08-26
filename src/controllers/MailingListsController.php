<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\controllers;

use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\MailingListElement;

use Craft;
use craft\base\Field;
use craft\helpers\DateTimeHelper;
use craft\web\Controller;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

/**
 * MailingListsController
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 */
class MailingListsController extends Controller
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
        $this->requirePermission('campaign:mailingLists');
    }

    /**
     * @param string                  $mailingListTypeHandle The mailing list type’s handle
     * @param int|null                $mailingListId         The mailing list’s ID, if editing an existing mailingList.
     * @param MailingListElement|null $mailingList           The mailing list being edited, if there were any validation errors.
     *
     * @return Response
     * @throws NotFoundHttpException if the requested mailing list is not found
     * @throws InvalidConfigException
     */
    public function actionEditMailingList(string $mailingListTypeHandle, int $mailingListId = null, MailingListElement $mailingList = null): Response
    {
        $variables = [];

        // Get the mailing list type
        // ---------------------------------------------------------------------

        if (!empty($mailingListTypeHandle)) {
            $variables['mailingListType'] = Campaign::$plugin->mailingListTypes->getMailingListTypeByHandle($mailingListTypeHandle);
        }

        if (empty($variables['mailingListType'])) {
            throw new NotFoundHttpException(Craft::t('campaign', 'Mailing list type not found.'));
        }

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
                $mailingList->mailingListTypeId = $variables['mailingListType']->id;
                $mailingList->enabled = true;
            }
        }

        $mailingList->fieldLayoutId = $variables['mailingListType']->fieldLayoutId;


        // Set the variables
        // ---------------------------------------------------------------------

        $variables['mailingListTypeHandle'] = $mailingListTypeHandle;
        $variables['mailingListId'] = $mailingListId;
        $variables['mailingList'] = $mailingList;

        // Set the title and slug
        // ---------------------------------------------------------------------

        if ($mailingListId === null) {
            $variables['title'] = Craft::t('campaign', 'Create a new mailing list');
        } else {
            $variables['title'] = $mailingList->title;
            $variables['slug'] = $mailingList->slug;
        }

        // Define the content tabs
        // ---------------------------------------------------------------------

        $variables['tabs'] = [];

        foreach ($variables['mailingListType']->getFieldLayout()->getTabs() as $index => $tab) {
            // Do any of the fields on this tab have errors?
            $hasErrors = false;

            if ($mailingList->hasErrors()) {
                foreach ($tab->getFields() as $field) {
                    /** @var Field $field */
                    if ($mailingList->getErrors($field->handle)) {
                        $hasErrors = true;
                        break;
                    }
                }
            }

            $variables['tabs'][] = [
                'label' => Craft::t('site', $tab->name),
                'url' => '#tab'.($index + 1),
                'class' => $hasErrors ? 'error' : null,
            ];
        }

        // Add default tab if missing
        if (empty($variables['tabs'])) {
            $variables['tabs'][] = [
                'label' => Craft::t('campaign', 'Mailing List'),
                'url' => '#tab1',
            ];
        }

        // Add report tab
        if ($mailingListId !== null) {
            $variables['tabs'][] = [
                'label' => Craft::t('campaign', 'Report'),
                'url' => '#tab-report',
                'class' => 'tab-report',
            ];
        }

        // Get the settings
        $variables['settings'] = Campaign::$plugin->getSettings();

        // Full page form variables
        $variables['fullPageForm'] = true;
        $variables['continueEditingUrl'] = 'campaign/mailinglists/'.$mailingListTypeHandle.'/{id}';
        $variables['saveShortcutRedirect'] = $variables['continueEditingUrl'];

        // Render the template
        return $this->renderTemplate('campaign/mailinglists/_edit', $variables);
    }

    /**
     * @return Response|null
     * @throws NotFoundHttpException
     * @throws \Throwable
     * @throws Exception
     * @throws BadRequestHttpException
     */
    public function actionSaveMailingList()
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
        if ((bool)$request->getBodyParam('duplicate')) {
            try {
                $mailingList = Craft::$app->getElements()->duplicateElement($mailingList);
            }
            catch (\Throwable $e) {
                throw new ServerErrorHttpException(Craft::t('campaign', 'An error occurred when duplicating the mailing list.'), 0, $e);
            }
        }

        // Set the title and slug
        $mailingList->title = $request->getBodyParam('title', $mailingList->title);
        $mailingList->slug = $request->getBodyParam('slug', $mailingList->slug);

        // Set the attributes, defaulting to the existing values for whatever is missing from the post data
        $mailingList->enabled = (bool)$request->getBodyParam('enabled', $mailingList->enabled);

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
                'mailingList' => $mailingList
            ]);

            return null;
        }

        if ($request->getAcceptsJson()) {
            $return = [];

            $return['success'] = true;
            $return['id'] = $mailingList->id;
            $return['title'] = $mailingList->title;

            if (!$request->getIsConsoleRequest() AND $request->getIsCpRequest()) {
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
     * Deletes a mailing list
     *
     * @return Response|null
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     * @throws \Throwable
     */
    public function actionDeleteMailingList()
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
                'mailingList' => $mailingList
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
