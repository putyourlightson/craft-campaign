<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\controllers;

use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\SegmentElement;

use Craft;
use craft\errors\ElementNotFoundException;
use craft\helpers\DateTimeHelper;
use craft\web\Controller;
use putyourlightson\campaign\helpers\SegmentHelper;
use Throwable;
use yii\base\Exception;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

/**
 * SegmentsController
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 */
class SegmentsController extends Controller
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
        $this->requirePermission('campaign:segments');

        // Require pro
        Campaign::$plugin->requirePro();
    }

    /**
     * @param string $segmentType The segment type
     * @param int|null $segmentId The segment’s ID, if editing an existing segment.
     * @param string|null $siteHandle
     * @param SegmentElement|null $segment The segment being edited, if there were any validation errors.
     *
     * @return Response
     * @throws NotFoundHttpException if the requested segment is not found
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
            }
            else {
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
        $variables['continueEditingUrl'] = 'campaign/segments/'.$segmentType.'/{id}';
        $variables['saveShortcutRedirect'] = $variables['continueEditingUrl'];

        // Render the template
        return $this->renderTemplate('campaign/segments/_edit', $variables);
    }

    /**
     * @return Response|null
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException if reasons
     * @throws Throwable
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws BadRequestHttpException
     */
    public function actionSaveSegment()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $segmentId = $request->getBodyParam('segmentId');

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
        if ((bool)$request->getBodyParam('duplicate')) {
            try {
                /** @var SegmentElement $segment */
                $segment = Craft::$app->getElements()->duplicateElement($segment);
            }
            catch (Throwable $e) {
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

                // Unset variable reference to avoid possible side-effects
                unset($orCondition);
            }

            // Unset variable reference to avoid possible side-effects
            unset($andCondition);
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
                'segment' => $segment
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
     * Deletes a segment
     *
     * @return Response|null
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     * @throws Throwable
     */
    public function actionDeleteSegment()
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
                'segment' => $segment
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
