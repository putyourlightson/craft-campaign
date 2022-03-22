<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\controllers;

use Craft;
use craft\base\Element;
use craft\controllers\CategoriesController;
use craft\helpers\Cp;
use craft\helpers\ElementHelper;
use craft\web\Controller;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\MailingListElement;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

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
            throw new ForbiddenHttpException('User not authorized to edit content in any sites.');
        }

        $user = Craft::$app->getUser()->getIdentity();

        $mailingList = Craft::createObject(MailingListElement::class);
        $mailingList->siteId = $site->id;
        $mailingList->mailingListTypeId = $mailingListType->id;
        $mailingList->slug = ElementHelper::tempSlug();

        if (!$mailingList->canSave($user)) {
            throw new ForbiddenHttpException('User not authorized to save this mailing list.');
        }

        // Save it
        $mailingList->setScenario(Element::SCENARIO_ESSENTIALS);
        if (!Craft::$app->getDrafts()->saveElementAsDraft($mailingList, Craft::$app->getUser()->getId(), null, null, false)) {
            throw new ServerErrorHttpException(sprintf('Unable to save mailing list as a draft: %s', implode(', ', $mailingList->getErrorSummary(true))));
        }

        // Redirect to its edit page
        return $this->redirect($mailingList->getCpEditUrl());
    }
}
