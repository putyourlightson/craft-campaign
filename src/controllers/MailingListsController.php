<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\controllers;

use Craft;
use craft\base\Element;
use craft\controllers\CategoriesController;
use craft\errors\SiteNotFoundException;
use craft\helpers\Cp;
use craft\helpers\ElementHelper;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\MailingListElement;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

class MailingListsController extends Controller
{
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

        $site = Cp::requestedSite();
        if (!$site) {
            throw new SiteNotFoundException();
        }

        // Create & populate the draft
        $mailingList = Craft::createObject(MailingListElement::class);
        $mailingList->siteId = $site->id;
        $mailingList->mailingListTypeId = $mailingListType->id;

        // Make sure the user is allowed to create this mailing list
        $user = Craft::$app->getUser()->getIdentity();
        if (!$mailingList->canSave($user)) {
            throw new ForbiddenHttpException('User not authorized to save this mailing list.');
        }

        // Title & slug
        $mailingList->title = $this->request->getQueryParam('title');
        $mailingList->slug = $this->request->getQueryParam('slug');
        if ($mailingList->title && !$mailingList->slug) {
            $mailingList->slug = ElementHelper::generateSlug($mailingList->title, null, $site->language);
        }
        if (!$mailingList->slug) {
            $mailingList->slug = ElementHelper::tempSlug();
        }

        // Save it
        $mailingList->setScenario(Element::SCENARIO_ESSENTIALS);
        if (!Craft::$app->getDrafts()->saveElementAsDraft($mailingList, Craft::$app->getUser()->getId(), null, null, false)) {
            return $this->asModelFailure($mailingList, Craft::t('app', 'Couldn’t create {type}.', [
                'type' => MailingListElement::lowerDisplayName(),
            ]), 'mailingList');
        }

        $editUrl = $mailingList->getCpEditUrl();

        $response = $this->asModelSuccess($mailingList, Craft::t('app', '{type} created.', [
            'type' => MailingListElement::displayName(),
        ]), 'mailingList', array_filter([
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
