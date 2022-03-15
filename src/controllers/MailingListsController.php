<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\controllers;

use Craft;
use craft\controllers\CategoriesController;
use craft\web\Controller;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\MailingListElement;
use yii\web\BadRequestHttpException;
use yii\web\Response;

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
     * Creates a new unpublished draft and redirects to its edit page.
     *
     * @see CategoriesController::actionCreate()
     * @since 2.0.0
     */
    public function actionCreate(string $mailingListTypeHandle): Response
    {
        $mailingListType = Campaign::$plugin->mailingListTypes->getMailingListTypeByHandle($mailingListTypeHandle);

        if (!$mailingListType) {
            throw new BadRequestHttpException("Invalid mailing list type handle: $mailingListType");
        }

        /**
         * The create action expects attributes to be passed in as body params.
         * @see ElementsController::actionCreate()
         */
        $this->request->setBodyParams([
            'elementType' => MailingListElement::class,
            'mailingListTypeId' => $mailingListType->id,
        ]);

        return Craft::$app->runAction('elements/create');
    }

    /**
     * Main edit page.
     */
    public function actionEdit(int $mailingListId = null): Response
    {
        // Set the selected subnav item by adding it to the global variables
        Craft::$app->view->getTwig()->addGlobal('selectedSubnavItem', 'mailinglists');

        return Craft::$app->runAction('elements/edit', [
            'elementId' => $mailingListId,
        ]);
    }
}
