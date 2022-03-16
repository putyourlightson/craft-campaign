<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\controllers;

use Craft;
use craft\web\Controller;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\SegmentElement;
use yii\web\BadRequestHttpException;
use yii\web\Response;

class SegmentsController extends Controller
{
    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        // Require pro
        Campaign::$plugin->requirePro();

        // Require permission
        $this->requirePermission('campaign:segments');

        return parent::beforeAction($action);
    }

    /**
     * Creates a new unpublished draft and redirects to its edit page.
     *
     * @see CategoriesController::actionCreate()
     * @since 2.0.0
     */
    public function actionCreate(string $segmentType): Response
    {
        if (!isset(SegmentElement::segmentTypes()[$segmentType])) {
            throw new BadRequestHttpException("Invalid segment type: $segmentType");
        }

        /**
         * The create action expects attributes to be passed in as body params.
         * @see ElementsController::actionCreate()
         */
        $this->request->setBodyParams([
            'elementType' => SegmentElement::class,
            'segmentType' => $segmentType,
        ]);

        return Craft::$app->runAction('elements/create');
    }

    /**
     * Main edit page.
     */
    public function actionEdit(int $segmentId): Response
    {
        // Set the selected subnav item by adding it to the global variables
        Craft::$app->view->getTwig()->addGlobal('selectedSubnavItem', 'segments');

        return Craft::$app->runAction('elements/edit', [
            'elementId' => $segmentId,
        ]);
    }
}
