<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\controllers;

use putyourlightson\campaign\Campaign;

use Craft;
use craft\web\Controller;
use putyourlightson\campaign\elements\MailingListElement;
use yii\web\BadRequestHttpException;
use yii\web\Response;

/**
 * @since 1.2.0
 */
class SyncController extends Controller
{
    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        // Require pro
        Campaign::$plugin->requirePro();

        // Require permission
        $this->requirePermission('campaign:syncContacts');

        return parent::beforeAction($action);
    }

    /**
     * Main sync page.
     */
    public function actionIndex(string $siteHandle = null, array $errors = []): Response
    {
        // Set the current site to the site handle if set
        if ($siteHandle !== null) {
            $site = Craft::$app->getSites()->getSiteByHandle($siteHandle);

            if ($site !== null) {
                Craft::$app->getSites()->setCurrentSite($site);
            }
        }

        $variables = [
            'mailingListElementType' => MailingListElement::class,
            'errors' => $errors,
        ];

        // Render the template
        return $this->renderTemplate('campaign/contacts/sync', $variables);
    }

    /**
     * Adds a synced user group.
     */
    public function actionAddSyncedMailingList(): ?Response
    {
        $this->requirePermission('campaign:sync');

        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $mailingListId = $request->getRequiredBodyParam('mailingListId');
        $mailingListId = (is_array($mailingListId) && isset($mailingListId[0])) ? $mailingListId[0] : null;

        if ($mailingListId === null) {
            Craft::$app->getSession()->setError(Craft::t('campaign', 'Couldn’t sync mailing list.'));

            // Send the errors back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'errors' => ['mailingListId' => [Craft::t('campaign', 'Mailing list is required.')]]
            ]);

            return null;
        }

        $mailingList = Campaign::$plugin->mailingLists->getMailingListById($mailingListId);

        if ($mailingList === null) {
            throw new BadRequestHttpException(Craft::t('campaign', 'Mailing list not found.'));
        }

        $userGroupId = $request->getRequiredBodyParam('userGroupId');

        if ($userGroupId === null) {
            throw new BadRequestHttpException('User group is required.');
        }

        $userGroup = Craft::$app->getUserGroups()->getGroupById($userGroupId);

        if ($userGroup === null) {
            throw new BadRequestHttpException(Craft::t('campaign', 'User group not found.'));
        }

        $mailingList->syncedUserGroupId = $userGroup->id;

        Craft::$app->getElements()->saveElement($mailingList);

        Campaign::$plugin->sync->queueSync($mailingList);

        if (Craft::$app->getRequest()->getAcceptsJson()) {
            return $this->asJson(['success' => true]);
        }

        Craft::$app->getSession()->setNotice(Craft::t('campaign', 'Mailing list successfully queued for syncing with user group.'));

        return $this->redirectToPostedUrl();
    }

    /**
     * Removes a synced user group.
     */
    public function actionRemoveSyncedMailingList(): Response
    {
        $this->requirePermission('campaign:sync');

        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $mailingListId = $request->getRequiredBodyParam('id');
        $mailingList = Campaign::$plugin->mailingLists->getMailingListById($mailingListId);

        if ($mailingList === null) {
            throw new BadRequestHttpException(Craft::t('campaign', 'Mailing list not found.'));
        }

        $mailingList->syncedUserGroupId = null;

        Craft::$app->getElements()->saveElement($mailingList);

        if (Craft::$app->getRequest()->getAcceptsJson()) {
            return $this->asJson(['success' => true]);
        }

        Craft::$app->getSession()->setNotice(Craft::t('campaign', 'Syncing successfully removed.'));

        return $this->redirectToPostedUrl();
    }
}
