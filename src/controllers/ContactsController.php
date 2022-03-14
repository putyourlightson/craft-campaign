<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\controllers;

use Craft;
use craft\controllers\ElementsController;
use craft\elements\User;
use craft\web\Controller;
use craft\web\CpScreenResponseBehavior;
use DateTime;
use putyourlightson\campaign\assets\ContactEditAsset;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\ContactElement;
use yii\web\NotFoundHttpException;
use yii\web\Response;

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
        /**
         * The create action expects `elementType` to be set in a body param.
         * @see ElementsController::actionCreate()
         */
        $this->request->setBodyParams([
            'elementType' => ContactElement::class,
        ]);

        return Craft::$app->runAction('elements/create');
    }

    /**
     * Main edit page.
     */
    public function actionEdit(int $elementId = null): Response
    {
        $this->view->registerAssetBundle(ContactEditAsset::class);

        /** @var Response|CpScreenResponseBehavior $response */
        $response = Craft::$app->runAction('elements/edit', [
            'elementId' => $elementId,
        ]);

        // Add actions
        $contact = Campaign::$plugin->contacts->getContactById($elementId);

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

        $response->addAltAction(
            Craft::t('campaign', 'Delete permanently'),
            [
                'action' => 'campaign/contacts/delete-permanently',
                'destructive' => 'true',
                'confirm' => Craft::t('campaign', 'Are you sure you want to permanently delete this contact? This action cannot be undone.'),
            ],
        );

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
     * Deletes a contact.
     */
    public function actionDelete(bool $hardDelete = false): ?Response
    {
        $this->requirePostRequest();

        $contact = $this->_getPostedContact();

        if (!Craft::$app->getElements()->deleteElement($contact, $hardDelete)) {
            if (Craft::$app->getRequest()->getAcceptsJson()) {
                return $this->asJson(['success' => false]);
            }

            Craft::$app->getSession()->setError(Craft::t('campaign', 'Couldn’t delete contact.'));

            // Send the contact back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'contact' => $contact,
            ]);

            return null;
        }

        if (Craft::$app->getRequest()->getAcceptsJson()) {
            return $this->asJson(['success' => true]);
        }

        Craft::$app->getSession()->setNotice(Craft::t('campaign', 'Contact deleted.'));

        return $this->redirectToPostedUrl($contact);
    }

    /**
     * Deletes a contact permanently.
     */
    public function actionDeletePermanently(): ?Response
    {
        return $this->actionDelete(true);
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
        $contactId = Craft::$app->getRequest()->getRequiredBodyParam('elementId');
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

        $mailingListId = Craft::$app->getRequest()->getRequiredBodyParam('mailingListId');
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

        if (Craft::$app->getRequest()->getAcceptsJson()) {
            return $this->asJson([
                'success' => true,
                'subscriptionStatus' => $subscriptionStatus,
                'subscriptionStatusLabel' => Craft::t('campaign', $subscriptionStatus ?: 'none'),
            ]);
        }

        return $this->redirectToPostedUrl();
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
            if (Craft::$app->getRequest()->getAcceptsJson()) {
                return $this->asJson(['success' => false]);
            }

            Craft::$app->getSession()->setError(Craft::t('campaign', 'Couldn’t mark contact as ' . $status . '.'));

            // Send the contact back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'contact' => $contact,
            ]);

            return null;
        }

        if (Craft::$app->getRequest()->getAcceptsJson()) {
            return $this->asJson(['success' => true]);
        }

        Craft::$app->getSession()->setNotice(Craft::t('campaign', 'Contact marked as ' . $status . '.'));

        return $this->redirectToPostedUrl($contact);
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
            if (Craft::$app->getRequest()->getAcceptsJson()) {
                return $this->asJson(['success' => false]);
            }

            Craft::$app->getSession()->setError(Craft::t('campaign', 'Couldn’t unmark contact as ' . $status . '.'));

            // Send the contact back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'contact' => $contact,
            ]);

            return null;
        }

        if (Craft::$app->getRequest()->getAcceptsJson()) {
            return $this->asJson(['success' => true]);
        }

        Craft::$app->getSession()->setNotice(Craft::t('campaign', 'Contact unmarked as ' . $status . '.'));

        return $this->redirectToPostedUrl($contact);
    }
}
