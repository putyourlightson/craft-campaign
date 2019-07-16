<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\controllers;

use craft\errors\MissingComponentException;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\helpers\RecaptchaHelper;
use putyourlightson\campaign\helpers\StringHelper;
use putyourlightson\campaign\models\PendingContactModel;

use Craft;
use craft\errors\ElementNotFoundException;
use craft\web\Controller;
use craft\web\View;
use Throwable;
use yii\base\Exception;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * FormController
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.10.0
 */
class FormsController extends Controller
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
     * Subscribe contact
     *
     * @return Response|null
     * @throws NotFoundHttpException
     * @throws Throwable
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws BadRequestHttpException
     */
    public function actionSubscribeContact()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $settings = Campaign::$plugin->getSettings();

        // Validate reCAPTCHA if enabled
        if ($settings->reCaptcha) {
            RecaptchaHelper::validateRecaptcha($request->getParam('g-recaptcha-response'), $request->getUserIP());
        }

        // Get mailing list by slug
        $mailingListSlug = Craft::$app->getRequest()->getRequiredBodyParam('mailingList');
        $mailingList = Campaign::$plugin->mailingLists->getMailingListBySlug($mailingListSlug);

        if ($mailingList === null) {
            throw new NotFoundHttpException(Craft::t('campaign', 'Mailing list not found.'));
        }

        $email = $request->getRequiredBodyParam('email');
        $referrer = $request->getReferrer();

        // If double opt-in
        if ($mailingList->mailingListType->doubleOptIn) {
            // Create new contact to get field values
            $contact = new ContactElement();
            $contact->fieldLayoutId = $settings->contactFieldLayoutId;
            $contact->setFieldValuesFromRequest('fields');

            // Create pending contact
            $pendingContact = new PendingContactModel();
            $pendingContact->pid = StringHelper::uniqueId('p');
            $pendingContact->email = $email;
            $pendingContact->mailingListId = $mailingList->id;
            $pendingContact->source = $referrer;
            $pendingContact->fieldData = $contact->getSerializedFieldValues();

            // Validate pending contact
            if (!$pendingContact->validate()) {
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

            // Save pending contact
            Campaign::$plugin->contacts->savePendingContact($pendingContact);

            // Send verification email
            Campaign::$plugin->contacts->sendVerificationEmail($pendingContact, $mailingList);
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

            // Track subscribe
            Campaign::$plugin->forms->subscribeContact($contact, $mailingList, 'web', $referrer);
        }

        if ($request->getAcceptsJson()) {
            return $this->asJson(['success' => true]);
        }

        if ($request->getBodyParam('redirect')) {
            return $this->redirectToPostedUrl();
        }

        // Get template
        $template = $mailingList !== null ? $mailingList->getMailingListType()->subscribeSuccessTemplate : '';

        // Use message template if none was defined
        if ($template == '') {
            $template = 'campaign/message';

            // Set template mode to CP
            Craft::$app->getView()->setTemplateMode(View::TEMPLATE_MODE_CP);
        }

        return $this->renderTemplate($template, [
            'title' => 'Subscribed',
            'message' => $mailingList->mailingListType->doubleOptIn ? Craft::t('campaign', 'Thank you for subscribing to the mailing list. Please check your email for a confirmation link.') : Craft::t('campaign', 'You have successfully subscribed to the mailing list.'),
            'mailingList' => $mailingList,
        ]);
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
     * Unsubscribes a contact
     *
     * @return Response|null
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     */
    public function actionUnsubscribeContact()
    {
        $this->requirePostRequest();

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

        $mailingLists = [];

        // Get mailing lists by slugs
        $mailingListSlugs = Craft::$app->getRequest()->getRequiredBodyParam('mailingLists');

        foreach ($mailingListSlugs as $mailingListSlug) {
            $mailingList = Campaign::$plugin->mailingLists->getMailingListBySlug($mailingListSlug);

            if ($mailingList !== null) {
                $mailingLists[] = $mailingList;
            }
        }

        Campaign::$plugin->forms->unsubscribeContact($contact, $mailingLists);

        if ($request->getAcceptsJson()) {
            return $this->asJson(['success' => true]);
        }

        return $this->redirectToPostedUrl($contact);
    }

    // Private Methods
    // =========================================================================

    /**
     * Gets contact by CID, verified by UID
     *
     * @return ContactElement|null
     */
    private function _getVerifiedContact()
    {
        $request = Craft::$app->getRequest();

        $cid = $request->getParam('cid');
        $uid = $request->getBodyParam('uid');

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
