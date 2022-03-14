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
use craft\web\CpScreenResponseBehavior;
use DateTime;
use putyourlightson\campaign\assets\CampaignEditAsset;
use putyourlightson\campaign\assets\ContactEditAsset;
use putyourlightson\campaign\assets\ReportsAsset;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\CampaignElement;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\records\ContactCampaignRecord;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

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

        $site = Cp::requestedSite();

        if (!$site) {
            throw new ForbiddenHttpException('User not authorized to edit content in any sites.');
        }

        $user = Craft::$app->getUser()->getIdentity();

        $mailingList = Craft::createObject(MailingListElement::class);
        $mailingList->siteId = $site->id;
        $mailingList->mailingListTypeId = $mailingListType->id;

        if (!$mailingList->canSave($user)) {
            throw new ForbiddenHttpException('User not authorized to save this mailing list.');
        }

        $mailingList->title = $this->request->getQueryParam('title');
        $mailingList->slug = $this->request->getQueryParam('slug');
        if ($mailingList->title && !$mailingList->slug) {
            $mailingList->slug = ElementHelper::generateSlug($mailingList->title, null, $site->language);
        }
        if (!$mailingList->slug) {
            $mailingList->slug = ElementHelper::tempSlug();
        }

        $mailingList->setScenario(Element::SCENARIO_ESSENTIALS);
        if (!Craft::$app->getDrafts()->saveElementAsDraft($mailingList, Craft::$app->getUser()->getId(), null, null, false)) {
            throw new ServerErrorHttpException(sprintf('Unable to save mailing list as a draft: %s', implode(', ', $mailingList->getErrorSummary(true))));
        }

        return $this->redirect($mailingList->getCpEditUrl());
    }

    /**
     * Main edit page.
     */
    public function actionEdit(int $mailingListId = null): Response
    {
        $this->view->registerAssetBundle(ContactEditAsset::class);
        $this->view->registerAssetBundle(ReportsAsset::class);

        // Set the selected subnav item by adding it to the global variables
        Craft::$app->view->getTwig()->addGlobal('selectedSubnavItem', 'mailinglists');

        return Craft::$app->runAction('elements/edit', [
            'elementId' => $mailingListId,
        ]);
    }
}
