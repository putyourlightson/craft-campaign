<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\controllers;

use Craft;
use craft\helpers\DateTimeHelper;
use craft\web\Controller;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\SegmentElement;
use Throwable;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
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
    public function actionEdit(int $segmentId = null): Response
    {
        // Set the selected subnav item by adding it to the global variables
        Craft::$app->view->getTwig()->addGlobal('selectedSubnavItem', 'segments');

        return Craft::$app->runAction('elements/edit', [
            'elementId' => $segmentId,
        ]);
    }

    /**
     * Saves a segment.
     */
    public function actionSave(): ?Response
    {
        $this->requirePostRequest();

        $segmentId = $this->request->getBodyParam('segmentId');

        if ($segmentId) {
            $segment = Campaign::$plugin->segments->getSegmentById($segmentId);

            if ($segment === null) {
                throw new NotFoundHttpException(Craft::t('campaign', 'Segment not found.'));
            }
        }
        else {
            $segment = new SegmentElement();
        }

        // If this segment should be duplicated then swap it for a duplicate
        if ($this->request->getBodyParam('duplicate')) {
            try {
                /** @var SegmentElement $segment */
                $segment = Craft::$app->getElements()->duplicateElement($segment);
            }
            catch (Throwable $e) {
                throw new ServerErrorHttpException(Craft::t('campaign', 'An error occurred when duplicating the segment.'), 0, $e);
            }
        }

        $segment->siteId = $this->request->getBodyParam('siteId', $segment->siteId);
        $segment->segmentType = $this->request->getBodyParam('segmentType', $segment->segmentType);
        $segment->enabled = (bool)$this->request->getBodyParam('enabled', $segment->enabled);
        $segment->title = $this->request->getBodyParam('title', $segment->title);
        $segment->slug = $this->request->getBodyParam('slug', $segment->slug);

        // Get the conditions
        $segment->conditions = $this->request->getBodyParam('conditions', $segment->conditions);

        if (is_array($segment->conditions)) {
            /** @var array $andCondition */
            foreach ($segment->conditions as &$andCondition) {
                foreach ($andCondition as &$orCondition) {
                    // Sort or conditions by keys
                    ksort($orCondition);
                }
            }
        }

        // Save it
        if (!Craft::$app->getElements()->saveElement($segment)) {
            if ($this->request->getAcceptsJson()) {
                return $this->asJson([
                    'errors' => $segment->getErrors(),
                ]);
            }

            Craft::$app->getSession()->setError(Craft::t('campaign', 'Couldn’t save segment.'));

            // Send the segment back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'segment' => $segment,
            ]);

            return null;
        }

        if ($this->request->getAcceptsJson()) {
            $return = [];

            $return['success'] = true;
            $return['id'] = $segment->id;
            $return['title'] = $segment->title;

            if (!$this->request->getIsConsoleRequest() && $this->request->getIsCpRequest()) {
                $return['cpEditUrl'] = $segment->getCpEditUrl();
            }

            $return['dateCreated'] = DateTimeHelper::toIso8601($segment->dateCreated);
            $return['dateUpdated'] = DateTimeHelper::toIso8601($segment->dateUpdated);

            return $this->asJson($return);
        }

        Craft::$app->getSession()->setNotice(Craft::t('campaign', 'Segment saved.'));

        return $this->redirectToPostedUrl($segment);
    }

    /**
     * Deletes a segment.
     */
    public function actionDelete(): ?Response
    {
        $this->requirePostRequest();

        $segmentId = $this->request->getRequiredBodyParam('segmentId');
        $segment = Campaign::$plugin->segments->getSegmentById($segmentId);

        if ($segment === null) {
            throw new NotFoundHttpException(Craft::t('campaign', 'Segment not found.'));
        }

        if (!Craft::$app->getElements()->deleteElement($segment)) {
            if ($this->request->getAcceptsJson()) {
                return $this->asJson(['success' => false]);
            }

            Craft::$app->getSession()->setError(Craft::t('campaign', 'Couldn’t delete segment.'));

            // Send the segment back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'segment' => $segment,
            ]);

            return null;
        }

        if ($this->request->getAcceptsJson()) {
            return $this->asJson(['success' => true]);
        }

        Craft::$app->getSession()->setNotice(Craft::t('campaign', 'Segment deleted.'));

        return $this->redirectToPostedUrl($segment);
    }
}
