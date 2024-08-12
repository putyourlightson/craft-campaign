<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\controllers;

use Craft;
use craft\base\Element;
use craft\errors\SiteNotFoundException;
use craft\helpers\Cp;
use craft\helpers\ElementHelper;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\SegmentElement;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

class SegmentsController extends Controller
{
    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        Campaign::$plugin->requirePro();

        return parent::beforeAction($action);
    }

    /**
     * Creates a new unpublished draft and redirects to its edit page.
     *
     * @see CategoriesController::actionCreate()
     * @since 2.0.0
     */
    public function actionCreate(): Response
    {
        $site = Cp::requestedSite();
        if (!$site) {
            throw new SiteNotFoundException();
        }

        // Create & populate the draft
        $segment = Craft::createObject(SegmentElement::class);
        $segment->siteId = $site->id;

        // Make sure the user is allowed to create this segment
        $user = Craft::$app->getUser()->getIdentity();
        if (!$segment->canSave($user)) {
            throw new ForbiddenHttpException('User not authorized to save this segment.');
        }

        // Title & slug
        $segment->title = $this->request->getQueryParam('title');
        $segment->slug = $this->request->getQueryParam('slug');
        if ($segment->title && !$segment->slug) {
            $segment->slug = ElementHelper::generateSlug($segment->title, null, $site->language);
        }
        if (!$segment->slug) {
            $segment->slug = ElementHelper::tempSlug();
        }

        // Save it
        $segment->setScenario(Element::SCENARIO_ESSENTIALS);
        if (!Craft::$app->getDrafts()->saveElementAsDraft($segment, Craft::$app->getUser()->getId(), null, null, false)) {
            return $this->asModelFailure($segment, Craft::t('app', 'Couldn’t create {type}.', [
                'type' => SegmentElement::lowerDisplayName(),
            ]), 'segment');
        }

        $editUrl = $segment->getCpEditUrl();

        $response = $this->asModelSuccess($segment, Craft::t('app', '{type} created.', [
            'type' => SegmentElement::displayName(),
        ]), 'segment', array_filter([
            'cpEditUrl' => $this->request->isCpRequest ? $editUrl : null,
        ]));

        if (!$this->request->getAcceptsJson()) {
            $response->redirect(UrlHelper::urlWithParams($editUrl, [
                'fresh' => 1,
            ]));
        }

        return $response;
    }
}
