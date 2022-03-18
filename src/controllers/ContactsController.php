<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\controllers;

use Craft;
use craft\base\Element;
use craft\elements\User;
use craft\web\Controller;
use craft\web\CpScreenResponseBehavior;
use DateTime;
use putyourlightson\campaign\assets\ContactEditAsset;
use putyourlightson\campaign\assets\ReportsAsset;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\ContactElement;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

class ContactsController extends Controller
{
    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        // Require permission
        $this->requirePermission('campaign:contacts');

        return parent::beforeAction($action);
    }

    /**
     * Main contacts page.
     */
    public function actionIndex(string $siteHandle = null): Response
    {
        // Set the current site to the site handle if set
        if ($siteHandle !== null) {
            $site = Craft::$app->getSites()->getSiteByHandle($siteHandle);

            if ($site !== null) {
                Craft::$app->getSites()->setCurrentSite($site);
            }
        }

        // Render the template
        return $this->renderTemplate('campaign/contacts/view');
    }

    /**
     * Creates a new unpublished draft and redirects to its edit page.
     *
     * @since 2.0.0
     */
    public function actionCreate(): Response
    {
        $user = Craft::$app->getUser()->getIdentity();
        $contact = Craft::createObject(ContactElement::class);

        if (!$contact->canSave($user)) {
            throw new ForbiddenHttpException('User not authorized to save this contact.');
        }

        // Save it
        $contact->setScenario(Element::SCENARIO_ESSENTIALS);
        if (!Craft::$app->getDrafts()->saveElementAsDraft($contact, Craft::$app->getUser()->getId(), null, null, false)) {
            throw new ServerErrorHttpException(sprintf('Unable to save contact as a draft: %s', implode(', ', $contact->getErrorSummary(true))));
        }

        // Redirect to its edit page
        return $this->redirect($contact->getCpEditUrl());
    }

    /**
     * Main edit page.
     */
    public function actionEdit(int $contactId = null): Response
    {
        // Set the selected subnav item by adding it to the global variables
        Craft::$app->view->getTwig()->addGlobal('selectedSubnavItem', 'contacts');

        /** @var Response|CpScreenResponseBehavior $response */
        $response = Craft::$app->runAction('elements/edit', [
            'elementId' => $contactId,
        ]);

        $contact = Campaign::$plugin->contacts->getContactById($contactId);

        if ($contact === null) {
            return $response;
        }

        if ($contact->complained === null) {
            $response->addAltAction(
                Craft::t('campaign', 'Mark contact as complained'),
                [
                    'action' => 'campaign/contacts/mark-complained',
                    'confirm' => Craft::t('campaign', 'Are you sure you want to mark this contact as complained?'),
                ],
            );
        }
        else {
            $response->addAltAction(
                Craft::t('campaign', 'Unmark contact as complained'),
                [
                    'action' => 'campaign/contacts/unmark-complained',
                    'confirm' => Craft::t('campaign', 'Are you sure you want to unmark this contact as complained?'),
                ],
            );
        }

        if ($contact->bounced === null) {
            $response->addAltAction(
                Craft::t('campaign', 'Mark contact as bounced'),
                [
                    'action' => 'campaign/contacts/mark-bounced',
                    'confirm' => Craft::t('campaign', 'Are you sure you want to mark this contact as bounced?'),
                ],
            );
        }
        else {
            $response->addAltAction(
                Craft::t('campaign', 'Unmark contact as bounced'),
                [
                    'action' => 'campaign/contacts/unmark-bounced',
                    'confirm' => Craft::t('campaign', 'Are you sure you want to unmark this contact as bounced?'),
                ],
            );
        }

        if ($contact->blocked === null) {
            $response->addAltAction(
                Craft::t('campaign', 'Mark contact as blocked'),
                [
                    'action' => 'campaign/contacts/mark-blocked',
                    'confirm' => Craft::t('campaign', 'Are you sure you want to mark this contact as blocked?'),
                ],
            );
        }
        else {
            $response->addAltAction(
                Craft::t('campaign', 'Unmark contact as blocked'),
                [
                    'action' => 'campaign/contacts/unmark-blocked',
                    'confirm' => Craft::t('campaign', 'Are you sure you want to unmark this contact as blocked?'),
                ],
            );
        }

        return $response;
    }

    /**
     * Marks a contact as complained.
     */
    public function actionMarkComplained(): ?Response
    {
        return $this->_markContactStatus('complained');
    }

    /**
     * Marks a contact as bounced.
     */
    public function actionMarkBounced(): ?Response
    {
        return $this->_markContactStatus('bounced');
    }

    /**
     * Marks a contact as blocked.
     */
    public function actionMarkBlocked(): ?Response
    {
        return $this->_markContactStatus('blocked');
    }

    /**
     * Unmarks a contact as complained.
     */
    public function actionUnmarkComplained(): ?Response
    {
        return $this->_unmarkContactStatus('complained');
    }

    /**
     * Unmarks a contact as bounced.
     */
    public function actionUnmarkBounced(): ?Response
    {
        return $this->_unmarkContactStatus('bounced');
    }

    /**
     * Unmarks a contact as blocked.
     */
    public function actionUnmarkBlocked(): ?Response
    {
        return $this->_unmarkContactStatus('blocked');
    }

    /**
     * Subscribes a contact to a mailing list.
     */
    public function actionSubscribeMailingList(): ?Response
    {
        return $this->_updateSubscription('subscribed');
    }

    /**
     * Unsubscribes a contact from a mailing list.
     */
    public function actionUnsubscribeMailingList(): ?Response
    {
        return $this->_updateSubscription('unsubscribed');
    }

    /**
     * Removes a contact from a mailing list.
     */
    public function actionRemoveMailingList(): ?Response
    {
        return $this->_updateSubscription('');
    }

    private function _getPostedContact(): ContactElement
    {
        // Accept either `elementId` or `contactId`
        $contactId = $this->request->getBodyParam('elementId');
        $contactId = $contactId ?? $this->request->getRequiredBodyParam('contactId');
        $contact = Campaign::$plugin->contacts->getContactById($contactId);

        if ($contact === null) {
            throw new NotFoundHttpException(Craft::t('campaign', 'Contact not found.'));
        }

        return $contact;
    }

    private function _updateSubscription(string $subscriptionStatus): ?Response
    {
        $this->requirePostRequest();

        $contact = $this->_getPostedContact();
        $mailingListId = $this->request->getRequiredBodyParam('mailingListId');
        $mailingList = Campaign::$plugin->mailingLists->getMailingListById($mailingListId);

        if ($mailingList === null) {
            throw new NotFoundHttpException(Craft::t('campaign', 'Mailing list not found.'));
        }

        if ($subscriptionStatus === '') {
            Campaign::$plugin->mailingLists->deleteContactSubscription($contact, $mailingList);
        }
        else {
            /** @var User|null $currentUser */
            $currentUser = Craft::$app->getUser()->getIdentity();
            $currentUserId = $currentUser ? $currentUser->id : '';
            Campaign::$plugin->mailingLists->addContactInteraction($contact, $mailingList, $subscriptionStatus, 'user', $currentUserId);
        }

        return $this->asSuccess(Craft::t('campaign', 'Subscription successfully updated.'), [
            'subscriptionStatus' => $subscriptionStatus,
            'subscriptionStatusLabel' => Craft::t('campaign', $subscriptionStatus ?: 'none'),
        ]);
    }

    /**
     * Marks a contact status.
     */
    private function _markContactStatus(string $status): ?Response
    {
        $this->requirePostRequest();

        $contact = $this->_getPostedContact();
        $contact->{$status} = new DateTime();

        if (!Craft::$app->getElements()->saveElement($contact)) {
            return $this->asModelFailure($contact, Craft::t('campaign', 'Couldn’t mark contact as ' . $status . '.'), 'contact');
        }

        return $this->asModelSuccess($contact, Craft::t('campaign', 'Contact marked as ' . $status . '.'), 'contact');
    }

    /**
     * Unmarks a contact status.
     */
    private function _unmarkContactStatus(string $status): ?Response
    {
        $this->requirePostRequest();

        $contact = $this->_getPostedContact();
        $contact->{$status} = null;

        if (!Craft::$app->getElements()->saveElement($contact)) {
            return $this->asModelFailure($contact, Craft::t('campaign', 'Couldn’t unmark contact as ' . $status . '.'), 'contact');
        }

        return $this->asModelSuccess($contact, Craft::t('campaign', 'Contact unmarked as ' . $status . '.'), 'contact');
    }
}
