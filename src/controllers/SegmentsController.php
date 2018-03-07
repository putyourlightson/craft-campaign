<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\controllers;

use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\SegmentElement;

use Craft;
use craft\errors\ElementNotFoundException;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use craft\web\Controller;
use yii\base\Exception;
use yii\web\BadRequestHttpException;
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
     * @param int|null              $segmentId    The segment’s ID, if editing an existing segment.
     * @param SegmentElement|null   $segment      The segment being edited, if there were any validation errors.
     *
     * @return Response
     * @throws NotFoundHttpException if the requested segment is not found
     */
    public function actionEditSegment(int $segmentId = null, SegmentElement $segment = null): Response
    {
        $variables = [];

        // Get the segment
        // ---------------------------------------------------------------------

        if ($segment === null) {
            if ($segmentId !== null) {
                $segment = Campaign::$plugin->segments->getSegmentById($segmentId);

                if ($segment === null) {
                    throw new NotFoundHttpException('Segment not found');
                }
            }
            else {
                $segment = new SegmentElement();
                $segment->enabled = true;
            }
        }

        // Set the variables
        // ---------------------------------------------------------------------

        $variables['segmentId'] = $segmentId;
        $variables['segment'] = $segment;

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

        // Full page form variables
        $variables['fullPageForm'] = true;
        $variables['continueEditingUrl'] = 'campaign/segments/{id}';
        $variables['saveShortcutRedirect'] = $variables['continueEditingUrl'];

        // Render the template
        return $this->renderTemplate('campaign/segments/_edit', $variables);
    }

    /**
     * @return Response|null
     * @throws NotFoundHttpException
     * @throws ServerErrorHttpException if reasons
     * @throws \Throwable
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
                throw new NotFoundHttpException('Segment not found');
            }
        }
        else {
            $segment = new SegmentElement();
        }

        // If this segment should be duplicated then swap it for a duplicate
        if ((bool)$request->getBodyParam('duplicate')) {
            try {
                $segment = Craft::$app->getElements()->duplicateElement($segment);
            }
            catch (\Throwable $e) {
                throw new ServerErrorHttpException(Craft::t('campaign', 'An error occurred when duplicating the segment.'), 0, $e);
            }
        }

        // Set the title and slug
        $segment->title = $request->getBodyParam('title', $segment->title);
        $segment->slug = $request->getBodyParam('slug', $segment->slug);

        // Set the attributes, defaulting to the existing values for whatever is missing from the post data
        $segment->enabled = (bool)$request->getBodyParam('enabled', $segment->enabled);

        // Get the conditions
        $segment->conditions = Craft::$app->getRequest()->getBodyParam('conditions', $segment->conditions);

        if (\is_array($segment->conditions)) {
            foreach ($segment->conditions as &$andCondition) {
                /* @var array $andCondition */
                foreach ($andCondition as &$orCondition) {
                    // Sort or conditions by keys
                    ksort($orCondition);
                }
            }
        }

        // JSON encode conditions
        $segment->conditions = Json::encode($segment->conditions);

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

            if (!$request->getIsConsoleRequest() AND $request->getIsCpRequest()) {
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
     * @throws \Throwable
     */
    public function actionDeleteSegment()
    {
        $this->requirePostRequest();

        $segmentId = Craft::$app->getRequest()->getRequiredBodyParam('segmentId');
        $segment = Campaign::$plugin->segments->getSegmentById($segmentId);

        if ($segment === null) {
            throw new NotFoundHttpException('Segment not found');
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
