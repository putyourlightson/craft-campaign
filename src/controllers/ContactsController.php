<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\controllers;

use Craft;
use craft\base\Element;
use craft\elements\User;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use DateTime;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\ContactElement;
use yii\web\ForbiddenHttpException;
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
     * Creates a new unpublished draft and redirects to its edit page.
     *
     * @since 2.0.0
     */
    public function actionCreate(): Response
    {
        $contact = Craft::createObject(ContactElement::class);

        // Make sure the user is allowed to create this contact
        $user = Craft::$app->getUser()->getIdentity();
        if (!$contact->canSave($user)) {
            throw new ForbiddenHttpException('User not authorized to save this contact.');
        }

        // Save it
        $contact->setScenario(Element::SCENARIO_ESSENTIALS);
        if (!Craft::$app->getDrafts()->saveElementAsDraft($contact, Craft::$app->getUser()->getId(), null, null, false)) {
            return $this->asModelFailure($contact, Craft::t('app', 'Couldn’t create {type}.', [
                'type' => ContactElement::lowerDisplayName(),
            ]), 'contact');
        }

        $editUrl = $contact->getCpEditUrl();

        $response = $this->asModelSuccess($contact, Craft::t('app', '{type} created.', [
            'type' => ContactElement::displayName(),
        ]), 'contact', array_filter([
            'cpEditUrl' => $this->request->isCpRequest ? $editUrl : null,
        ]));

        if (!$this->request->getAcceptsJson()) {
            $response->redirect(UrlHelper::urlWithParams($editUrl, [
                'fresh' => 1,
            ]));
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
            'subscriptionStatusLabel' => Craft::t('campaign', ucfirst($subscriptionStatus) ?: 'None'),
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
