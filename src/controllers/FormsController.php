<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\controllers;

use craft\errors\MissingComponentException;
use putyourlightson\campaign\base\BaseMessageController;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\helpers\RecaptchaHelper;
use putyourlightson\campaign\helpers\StringHelper;
use putyourlightson\campaign\models\PendingContactModel;

use Craft;
use craft\errors\ElementNotFoundException;
use Throwable;
use yii\base\Exception;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * FormController
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.10.0
 */
class FormsController extends BaseMessageController
{
    // Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected $allowAnonymous = true;

    // Public Methods
    // =========================================================================

    /**
     * Subscribes the provided email to a mailing list
     *
     * @return Response|null
     * @throws NotFoundHttpException
     * @throws Throwable
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws BadRequestHttpException
     */
    public function actionSubscribe()
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

        // If subscribe verification required
        if ($mailingList->mailingListType->subscribeVerificationRequired) {
            // Create new contact to get field values
            $contact = new ContactElement();
            $contact->fieldLayoutId = Campaign::$plugin->getSettings()->contactFieldLayoutId;
            $contact->setFieldValuesFromRequest('fields');

            // Create pending contact
            $pendingContact = new PendingContactModel();
            $pendingContact->pid = StringHelper::uniqueId('p');
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

                // Send the contact back to the template
                Craft::$app->getUrlManager()->setRouteParams([
                    'pendingContact' => $pendingContact
                ]);

                return null;
            }

            // Send verification email
            Campaign::$plugin->forms->sendVerifySubscribeEmail($pendingContact, $mailingList);
        }
        else {
            // Get contact if it exists
            $contact = Campaign::$plugin->contacts->getContactByEmail($email);

            if ($contact === null) {
                $contact = new ContactElement();
            }

            // Set field values
            $contact->email = $email;
            $contact->fieldLayoutId = Campaign::$plugin->getSettings()->contactFieldLayoutId;
            $contact->setFieldValuesFromRequest('fields');

            // Save contact
            if (!Craft::$app->getElements()->saveElement($contact)) {
                if ($request->getAcceptsJson()) {
                    return $this->asJson([
                        'errors' => $contact->getErrors(),
                    ]);
                }

                // Send the contact back to the template
                Craft::$app->getUrlManager()->setRouteParams([
                    'contact' => $contact
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

        // TODO: remove in 2.0.0
        // Render template defaulting to message (see [[BaseMessageController::renderTemplate()]])
        return $this->renderTemplate($mailingList->getMailingListType()->subscribeSuccessTemplate, [
            'title' => $mailingList->mailingListType->subscribeVerificationRequired ? Craft::t('campaign', 'Subscribed') : Craft::t('campaign', 'Subscribe'),
            'message' => $mailingList->mailingListType->subscribeVerificationRequired ? Craft::t('campaign', 'Thank you for subscribing to the mailing list. Please check your email for a verification link.') : Craft::t('campaign', 'You have successfully subscribed to the mailing list.'),
            'mailingList' => $mailingList,
        ]);
    }

    /**
     * Unsubscribes the provided email from a mailing list
     *
     * @return Response|null
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     */
    public function actionUnsubscribe()
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
            throw new ForbiddenHttpException('Unsubscribing through a form is not allowed.');
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
     * Updates a contact
     *
     * @return Response|null
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     * @throws MissingComponentException
     */
    public function actionUpdateContact()
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
                'contact' => $contact
            ]);

            return null;
        }

        if ($request->getAcceptsJson()) {
            return $this->asJson(['success' => true]);
        }

        return $this->redirectToPostedUrl($contact);
    }

    /**
     * Verifies and subscribes a pending contact to a mailing list
     *
     * @return Response
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws NotFoundHttpException
     * @throws Throwable
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
            throw new NotFoundHttpException(Craft::t('campaign', 'Verification link has expired'));
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

        // TODO: change template to `subscribeSuccessTemplate` in 2.0.0
        // Render template defaulting to message (see [[BaseMessageController::renderTemplate()]])
        return $this->renderTemplate($mailingList->getMailingListType()->subscribeVerificationSuccessTemplate, [
            'title' => Craft::t('campaign', 'Verified'),
            'message' => Craft::t('campaign', 'You have successfully verified your email address and subscribed to the mailing list.'),
            'mailingList' => $mailingList,
            'contact' => $contact,
        ]);
    }

    /**
     * Verifies and unsubscribes the provided contact from a mailing list
     *
     * @return Response|null
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     */
    public function actionVerifyUnsubscribe()
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

        // Render template defaulting to message (see [[BaseMessageController::renderTemplate()]])
        return $this->renderTemplate($mailingList->getMailingListType()->unsubscribeSuccessTemplate, [
            'title' => Craft::t('campaign', 'Unsubscribed'),
            'message' => Craft::t('campaign', 'You have successfully unsubscribed from the mailing list.'),
            'mailingList' => $mailingList,
        ]);
    }

    // Private Methods
    // =========================================================================

    /**
     * Validates reCAPTCHA if enabled
     *
     * @throws ForbiddenHttpException
     */
    private function _validateRecaptcha()
    {
        $request = Craft::$app->getRequest();

        // Validate reCAPTCHA if enabled
        if (Campaign::$plugin->getSettings()->reCaptcha) {
            RecaptchaHelper::validateRecaptcha($request->getParam('g-recaptcha-response'), $request->getUserIP());
        }
    }

    /**
     * Gets contact by CID, verified by UID
     *
     * @return ContactElement|null
     */
    private function _getVerifiedContact()
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
