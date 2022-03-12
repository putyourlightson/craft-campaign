<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\controllers;

use Craft;
use craft\helpers\App;
use putyourlightson\campaign\base\BaseMessageController;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\helpers\RecaptchaHelper;

use putyourlightson\campaign\models\PendingContactModel;
use yii\web\ForbiddenHttpException;
use yii\web\MethodNotAllowedHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class FormsController extends BaseMessageController
{
    /**
     * @inheritdoc
     */
    protected int|bool|array $allowAnonymous = true;

    /**
     * Subscribes the provided email to a mailing list.
     */
    public function actionSubscribe(): ?Response
    {
        $this->requirePostRequest();
        $this->_validateRecaptcha();

        $request = Craft::$app->getRequest();

        // Get mailing list by slug
        $mailingListSlug = $request->getRequiredParam('mailingList');
        $mailingList = Campaign::$plugin->mailingLists->getMailingListBySlug($mailingListSlug);

        if ($mailingList === null) {
            throw new NotFoundHttpException(Craft::t('campaign', 'Mailing list not found.'));
        }

        $email = $request->getRequiredParam('email');
        $referrer = $request->getReferrer();

        // Get contact if it exists
        $contact = Campaign::$plugin->contacts->getContactByEmail($email);

        if ($contact === null) {
            $contact = new ContactElement();
            $contact->email = $email;
        }

        // Disallow if blocked
        if ($contact->blocked !== null) {
            throw new MethodNotAllowedHttpException(Craft::t('campaign', 'This email address is blocked from subscribing.'));
        }

        // Set field values
        $contact->setFieldValuesFromRequest('fields');

        // If subscribe verification required
        if ($mailingList->getMailingListType()->subscribeVerificationRequired) {
            // Mock before save so we can validate the contact
            $contact->beforeSave(true);

            // Validate the contact
            if (!$contact->validate()) {
                if ($request->getAcceptsJson()) {
                    return $this->asJson([
                        'errors' => $contact->getErrors(),
                    ]);
                }

                // Send the contact back to the template
                Craft::$app->getUrlManager()->setRouteParams([
                    'contact' => $contact,
                ]);

                return null;
            }

            // Create pending contact
            $pendingContact = new PendingContactModel();
            $pendingContact->email = $email;
            $pendingContact->mailingListId = $mailingList->id;
            $pendingContact->source = $referrer;
            $pendingContact->fieldData = $contact->getSerializedFieldValues();

            // Save pending contact
            if (!Campaign::$plugin->pendingContacts->savePendingContact($pendingContact)) {
                if ($request->getAcceptsJson()) {
                    return $this->asJson([
                        'errors' => $pendingContact->getErrors(),
                    ]);
                }

                // Send the contact and the pending contact errors back to the template
                Craft::$app->getUrlManager()->setRouteParams([
                    'contact' => $contact,
                    'errors' => $pendingContact->getErrors(),
                ]);

                return null;
            }

            // Send verification email
            Campaign::$plugin->forms->sendVerifySubscribeEmail($pendingContact, $mailingList);
        }
        else {
            // Save contact
            if (!Craft::$app->getElements()->saveElement($contact)) {
                if ($request->getAcceptsJson()) {
                    return $this->asJson([
                        'errors' => $contact->getErrors(),
                    ]);
                }

                // Send the contact back to the template
                Craft::$app->getUrlManager()->setRouteParams([
                    'contact' => $contact,
                ]);

                return null;
            }

            Campaign::$plugin->forms->subscribeContact($contact, $mailingList, 'web', $referrer);
        }

        if ($request->getAcceptsJson()) {
            return $this->asJson(['success' => true]);
        }

        if ($request->getBodyParam('redirect')) {
            return $this->redirectToPostedUrl();
        }

        return $this->renderMessageTemplate($mailingList->getMailingListType()->subscribeSuccessTemplate, [
            'title' => $mailingList->mailingListType->subscribeVerificationRequired ? Craft::t('campaign', 'Subscribed') : Craft::t('campaign', 'Subscribe'),
            'message' => $mailingList->mailingListType->subscribeVerificationRequired ? Craft::t('campaign', 'Thank you for subscribing to the mailing list. Please check your email for a verification link.') : Craft::t('campaign', 'You have successfully subscribed to the mailing list.'),
            'mailingList' => $mailingList,
        ]);
    }

    /**
     * Unsubscribes the provided email from a mailing list.
     */
    public function actionUnsubscribe(): ?Response
    {
        $this->requirePostRequest();
        $this->_validateRecaptcha();

        $request = Craft::$app->getRequest();

        // Get mailing list by slug
        $mailingListSlug = $request->getRequiredParam('mailingList');
        $mailingList = Campaign::$plugin->mailingLists->getMailingListBySlug($mailingListSlug);

        if ($mailingList === null) {
            throw new NotFoundHttpException(Craft::t('campaign', 'Mailing list not found.'));
        }

        // Ensure unsubscribing through a form is allowed
        if (!$mailingList->getMailingListType()->unsubscribeFormAllowed) {
            throw new ForbiddenHttpException('Unsubscribing through a form is not allowed for this mailing list.');
        }

        $email = $request->getRequiredParam('email');

        // Get contact by email
        $contact = Campaign::$plugin->contacts->getContactByEmail($email);

        if ($contact === null) {
            throw new NotFoundHttpException(Craft::t('campaign', 'A contact with that email address could not be found.'));
        }

        // Send verification email
        Campaign::$plugin->forms->sendVerifyUnsubscribeEmail($contact, $mailingList);

        if ($request->getAcceptsJson()) {
            return $this->asJson(['success' => true]);
        }

        return $this->redirectToPostedUrl($contact);
    }

    /**
     * Updates a contact.
     */
    public function actionUpdateContact(): ?Response
    {
        $this->requirePostRequest();
        $this->_validateRecaptcha();

        $request = Craft::$app->getRequest();

        // Get verified contact
        $contact = $this->_getVerifiedContact();

        if ($contact === null) {
            $error = Craft::t('campaign', 'Contact not found.');

            if ($request->getAcceptsJson()) {
                return $this->asJson([
                    'errors' => [$error],
                ]);
            }

            throw new NotFoundHttpException($error);
        }

        // Set the field values using the fields location
        $fieldsLocation = $request->getParam('fieldsLocation', 'fields');
        $contact->setFieldValuesFromRequest($fieldsLocation);

        // Save it
        if (!Campaign::$plugin->forms->updateContact($contact)) {
            if ($request->getAcceptsJson()) {
                return $this->asJson([
                    'errors' => $contact->getErrors(),
                ]);
            }

            Craft::$app->getSession()->setError(Craft::t('campaign', 'Couldn’t save contact.'));

            // Send the contact back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'contact' => $contact,
            ]);

            return null;
        }

        if ($request->getAcceptsJson()) {
            return $this->asJson(['success' => true]);
        }

        return $this->redirectToPostedUrl($contact);
    }

    /**
     * Verifies and subscribes a pending contact to a mailing list.
     */
    public function actionVerifySubscribe(): Response
    {
        $request = Craft::$app->getRequest();

        // Get pending contact ID
        $pid = $request->getParam('pid');

        if ($pid === null) {
            throw new NotFoundHttpException(Craft::t('campaign', 'Invalid verification link.'));
        }

        // Verify pending contact
        $pendingContact = Campaign::$plugin->pendingContacts->verifyPendingContact($pid);

        if ($pendingContact === null) {
            if (!Campaign::$plugin->pendingContacts->getIsPendingContactTrashed($pid)) {
                throw new NotFoundHttpException(Craft::t('campaign', 'Verification link has expired'));
            }

            if ($request->getBodyParam('redirect')) {
                return $this->redirectToPostedUrl();
            }

            $mlid = $request->getParam('mlid');
            $mailingList = null;

            if ($mlid) {
                $mailingList = Campaign::$plugin->mailingLists->getMailingListById($mlid);
            }

            if ($mailingList) {
                return $this->renderMessageTemplate($mailingList->getMailingListType()->subscribeSuccessTemplate, [
                    'title' => Craft::t('campaign', 'Verified'),
                    'message' => Craft::t('campaign', 'You have successfully verified your email address and subscribed to the mailing list.'),
                    'mailingList' => $mailingList,
                    'contact' => null,
                ]);
            }
            else {
                return $this->renderMessageTemplate('', [
                    'title' => Craft::t('campaign', 'Verified'),
                    'message' => Craft::t('campaign', 'You have successfully verified your email address and subscribed to the mailing list.'),
                ]);
            }
        }

        // Get contact
        $contact = Campaign::$plugin->contacts->getContactByEmail($pendingContact->email);

        if ($contact === null) {
            throw new NotFoundHttpException(Craft::t('campaign', 'Contact not found.'));
        }

        // Get mailing list
        $mailingList = Campaign::$plugin->mailingLists->getMailingListById($pendingContact->mailingListId);

        if ($mailingList === null) {
            throw new NotFoundHttpException(Craft::t('campaign', 'Mailing list not found.'));
        }

        Campaign::$plugin->forms->subscribeContact($contact, $mailingList, 'web', $pendingContact->source, true);

        if ($request->getBodyParam('redirect')) {
            return $this->redirectToPostedUrl($contact);
        }

        return $this->renderMessageTemplate($mailingList->getMailingListType()->subscribeSuccessTemplate, [
            'title' => Craft::t('campaign', 'Verified'),
            'message' => Craft::t('campaign', 'You have successfully verified your email address and subscribed to the mailing list.'),
            'mailingList' => $mailingList,
            'contact' => $contact,
        ]);
    }

    /**
     * Verifies and unsubscribes the provided contact from a mailing list.
     */
    public function actionVerifyUnsubscribe(): ?Response
    {
        $request = Craft::$app->getRequest();

        // Get verified contact
        $contact = $this->_getVerifiedContact();

        if ($contact === null) {
            $error = Craft::t('campaign', 'Contact not found.');

            if ($request->getAcceptsJson()) {
                return $this->asJson([
                    'errors' => [$error],
                ]);
            }
            throw new NotFoundHttpException($error);
        }

        // Get mailing list by ID
        $mailingListId = Craft::$app->getRequest()->getRequiredParam('mlid');
        $mailingList = Campaign::$plugin->mailingLists->getMailingListById($mailingListId);

        if ($mailingList === null) {
            throw new NotFoundHttpException(Craft::t('campaign', 'Mailing list not found.'));
        }

        Campaign::$plugin->forms->unsubscribeContact($contact, $mailingList);

        if ($request->getBodyParam('redirect')) {
            return $this->redirectToPostedUrl($contact);
        }

        return $this->renderMessageTemplate($mailingList->getMailingListType()->unsubscribeSuccessTemplate, [
            'title' => Craft::t('campaign', 'Unsubscribed'),
            'message' => Craft::t('campaign', 'You have successfully unsubscribed from the mailing list.'),
            'mailingList' => $mailingList,
        ]);
    }

    /**
     * Validates reCAPTCHA if enabled.
     */
    private function _validateRecaptcha()
    {
        $request = Craft::$app->getRequest();

        // Validate reCAPTCHA if enabled
        if (Campaign::$plugin->getSettings()->reCaptcha) {
            $response = $request->getParam('g-recaptcha-response');

            if ($response === null) {
                throw new ForbiddenHttpException(App::parseEnv(Campaign::$plugin->getSettings()->reCaptchaErrorMessage));
            }

            RecaptchaHelper::validateRecaptcha($response, $request->getUserIP());
        }
    }

    /**
     * Gets contact by CID, verified by UID.
     */
    private function _getVerifiedContact(): ?ContactElement
    {
        $request = Craft::$app->getRequest();

        $cid = $request->getParam('cid');
        $uid = $request->getParam('uid');

        if ($cid === null) {
            return null;
        }

        $contact = Campaign::$plugin->contacts->getContactByCid($cid);

        if ($contact === null) {
            return null;
        }

        // Verify UID
        if ($uid === null || $contact->uid !== $uid) {
            return null;
        }

        return $contact;
    }
}
