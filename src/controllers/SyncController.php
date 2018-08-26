<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\controllers;

use putyourlightson\campaign\Campaign;

use Craft;
use craft\web\Controller;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * SyncController
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.2.0
 */
class SyncController extends Controller
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
        $this->requirePermission('campaign:syncMailingLists');
    }

    /**
     * Adds a synced user group
     *
     * @throws ForbiddenHttpException
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     */
    public function actionAddSyncedMailingList()
    {
        $this->requirePermission('campaign:sync');

        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $mailingListId = $request->getRequiredBodyParam('mailingListId');
        $mailingListId = (\is_array($mailingListId) AND isset($mailingListId[0])) ? $mailingListId[0] : null;

        if ($mailingListId === null) {
            Craft::$app->getSession()->setError(Craft::t('campaign', 'Couldn’t save mailing list.'));

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
     * Removes a synced user group
     *
     * @throws ForbiddenHttpException
     * @throws BadRequestHttpException
     */
    public function actionRemoveSyncedMailingList(): Response
    {
        $this->requirePermission('campaign:sync');

        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $mailingListId = $request->getRequiredBodyParam('id');
        $mailingList = Campaign::$plugin->mailingLists->getMailingListById($mailingListId);

        if ($mailingList === null) {
            throw new NotFoundHttpException(Craft::t('campaign', 'Mailing list not found.'));
        }

        $mailingList->syncedUserGroupId = null;

        Craft::$app->getElements()->saveElement($mailingList);

        if (Craft::$app->getRequest()->getAcceptsJson()) {
            return $this->asJson(['success' => true]);
        }

        Craft::$app->getSession()->setNotice(Craft::t('campaign', 'Mailing list syncing removed.'));

        return $this->redirectToPostedUrl();
    }
}