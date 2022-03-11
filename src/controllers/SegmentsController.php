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
use putyourlightson\campaign\helpers\SegmentHelper;
use Throwable;
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
     * Main edit page.
     *
     * @param SegmentElement|null $segment The segment being edited, if there were any validation errors.
     */
    public function actionEditSegment(string $segmentType, int $segmentId = null, string $siteHandle = null, SegmentElement $segment = null): Response
    {
        // Check that the segment type exists
        // ---------------------------------------------------------------------

        $segmentTypes = SegmentElement::segmentTypes();

        if (empty($segmentTypes[$segmentType])) {
            throw new NotFoundHttpException(Craft::t('campaign', 'Segment type not found.'));
        }

        // Get the segment
        // ---------------------------------------------------------------------

        if ($segment === null) {
            if ($segmentId !== null) {
                $segment = Campaign::$plugin->segments->getSegmentById($segmentId);

                if ($segment === null) {
                    throw new NotFoundHttpException(Craft::t('campaign', 'Segment not found.'));
                }
            } else {
                $segment = new SegmentElement();
                $segment->segmentType = $segmentType;
                $segment->enabled = true;
            }
        }

        // Get the site if site handle is set
        if ($siteHandle !== null) {
            $site = Craft::$app->getSites()->getSiteByHandle($siteHandle);

            $segment->siteId = $site->id;
        }

        // Set the variables
        // ---------------------------------------------------------------------

        $variables = [
            'segmentType' => $segmentType,
            'segmentId' => $segmentId,
            'segment' => $segment,
        ];

        // Set the title and slug
        // ---------------------------------------------------------------------

        if ($segmentId === null) {
            $variables['title'] = Craft::t('campaign', 'Create a new segment');
        } else {
            $variables['title'] = $segment->title;
            $variables['slug'] = $segment->slug;
        }

        // Get the settings
        $variables['settings'] = Campaign::$plugin->getSettings();

        // Get available fields and operators
        $variables['availableFields'] = SegmentHelper::getAvailableFields();
        $variables['fieldOperators'] = SegmentHelper::getFieldOperators();

        // Full page form variables
        $variables['fullPageForm'] = true;
        $variables['continueEditingUrl'] = 'campaign/segments/' . $segmentType . '/{id}';
        $variables['saveShortcutRedirect'] = $variables['continueEditingUrl'];

        // Render the template
        return $this->renderTemplate('campaign/segments/_edit', $variables);
    }

    /**
     * Saves a segment.
     */
    public function actionSaveSegment(): ?Response
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $segmentId = $request->getBodyParam('segmentId');

        if ($segmentId) {
            $segment = Campaign::$plugin->segments->getSegmentById($segmentId);

            if ($segment === null) {
                throw new NotFoundHttpException(Craft::t('campaign', 'Segment not found.'));
            }
        } else {
            $segment = new SegmentElement();
        }

        // If this segment should be duplicated then swap it for a duplicate
        if ($request->getBodyParam('duplicate')) {
            try {
                /** @var SegmentElement $segment */
                $segment = Craft::$app->getElements()->duplicateElement($segment);
            } catch (Throwable $e) {
                throw new ServerErrorHttpException(Craft::t('campaign', 'An error occurred when duplicating the segment.'), 0, $e);
            }
        }

        $segment->siteId = $request->getBodyParam('siteId', $segment->siteId);
        $segment->segmentType = $request->getBodyParam('segmentType', $segment->segmentType);
        $segment->enabled = (bool)$request->getBodyParam('enabled', $segment->enabled);
        $segment->title = $request->getBodyParam('title', $segment->title);
        $segment->slug = $request->getBodyParam('slug', $segment->slug);

        // Get the conditions
        $segment->conditions = Craft::$app->getRequest()->getBodyParam('conditions', $segment->conditions);

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
            if ($request->getAcceptsJson()) {
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

        if ($request->getAcceptsJson()) {
            $return = [];

            $return['success'] = true;
            $return['id'] = $segment->id;
            $return['title'] = $segment->title;

            if (!$request->getIsConsoleRequest() && $request->getIsCpRequest()) {
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
    public function actionDeleteSegment(): ?Response
    {
        $this->requirePostRequest();

        $segmentId = Craft::$app->getRequest()->getRequiredBodyParam('segmentId');
        $segment = Campaign::$plugin->segments->getSegmentById($segmentId);

        if ($segment === null) {
            throw new NotFoundHttpException(Craft::t('campaign', 'Segment not found.'));
        }

        if (!Craft::$app->getElements()->deleteElement($segment)) {
            if (Craft::$app->getRequest()->getAcceptsJson()) {
                return $this->asJson(['success' => false]);
            }

            Craft::$app->getSession()->setError(Craft::t('campaign', 'Couldn’t delete segment.'));

            // Send the segment back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'segment' => $segment,
            ]);

            return null;
        }

        if (Craft::$app->getRequest()->getAcceptsJson()) {
            return $this->asJson(['success' => true]);
        }

        Craft::$app->getSession()->setNotice(Craft::t('campaign', 'Segment deleted.'));

        return $this->redirectToPostedUrl($segment);
    }
}
