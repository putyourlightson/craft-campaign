<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\controllers;

use Craft;
use craft\base\Element;
use craft\helpers\Cp;
use craft\helpers\ElementHelper;
use craft\web\Controller;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\CampaignElement;
use putyourlightson\campaign\elements\SegmentElement;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

class SegmentsController extends Controller
{
    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        // Require pro
        Campaign::$plugin->requirePro();

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

        $site = Cp::requestedSite();

        if (!$site) {
            throw new ForbiddenHttpException('User not authorized to edit content in any sites.');
        }

        $user = Craft::$app->getUser()->getIdentity();

        $segment = Craft::createObject(SegmentElement::class);
        $segment->siteId = $site->id;
        $segment->segmentType = $segmentType;
        $segment->slug = ElementHelper::tempSlug();

        if (!$segment->canSave($user)) {
            throw new ForbiddenHttpException('User not authorized to save this segment.');
        }

        // Save it
        $segment->setScenario(Element::SCENARIO_ESSENTIALS);
        if (!Craft::$app->getDrafts()->saveElementAsDraft($segment, Craft::$app->getUser()->getId(), null, null, false)) {
            throw new ServerErrorHttpException(sprintf('Unable to save segment as a draft: %s', implode(', ', $segment->getErrorSummary(true))));
        }

        // Redirect to its edit page
        return $this->redirect($segment->getCpEditUrl());
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
