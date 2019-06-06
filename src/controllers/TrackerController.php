<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\controllers;

use craft\errors\MissingComponentException;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\SendoutElement;
use putyourlightson\campaign\helpers\RecaptchaHelper;
use putyourlightson\campaign\helpers\StringHelper;
use putyourlightson\campaign\models\PendingContactModel;
use putyourlightson\campaign\records\LinkRecord;

use Craft;
use craft\errors\ElementNotFoundException;
use craft\web\Controller;
use craft\web\View;
use Throwable;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * TrackerController
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 */
class TrackerController extends Controller
{
    // Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected $allowAnonymous = ['open', 'click', 'subscribe', 'unsubscribe', 'verify-email', 'update-contact'];

    // Public Methods
    // =========================================================================

    /**
     * Open
     *
     * @return Response|null
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws Throwable
     */
    public function actionOpen()
    {
        // Get contact and sendout
        $contact = $this->_getContact();
        $sendout = $this->_getSendout();

        if ($contact AND $sendout) {
            // Track open
            Campaign::$plugin->tracker->open($contact, $sendout);
        }

        // Return tracking image
        $filePath = Craft::getAlias('@putyourlightson/campaign/resources/images/t.gif');

        return Craft::$app->getResponse()->sendFile($filePath);
    }

    /**
     * Click
     *
     * @return Response
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws NotFoundHttpException
     * @throws Throwable
     */
    public function actionClick(): Response
    {
        // Get contact, sendout and link
        $contact = $this->_getContact();
        $sendout = $this->_getSendout();
        $linkRecord = $this->_getLink();

        if ($linkRecord === null) {
            throw new NotFoundHttpException(Craft::t('campaign', 'Link not found.'));
        }

        $url = $linkRecord->url;

        if ($contact AND $sendout) {
            // Track click
            Campaign::$plugin->tracker->click($contact, $sendout, $linkRecord);

            // If Google Analytics link tracking
            if ($sendout->googleAnalyticsLinkTracking) {
                $hasQuery = strpos($url, '?');
                $url .= $hasQuery === false ? '?' : '&';
                $url .= 'utm_source=campaign-plugin&utm_medium=email&utm_campaign='.$sendout->subject;
            }
        }

        // Redirect to URL
        return $this->redirect($url);
    }

    /**
     * Subscribe
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
            Campaign::$plugin->tracker->subscribe($contact, $mailingList, 'web', $referrer);
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
     * Unsubscribe
     *
     * @return Response|null
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function actionUnsubscribe()
    {
        // Get contact and sendout
        $contact = $this->_getContact();
        $sendout = $this->_getSendout();

        $mailingList = null;

        if ($contact AND $sendout) {
            // Track unsubscribe
            $mailingList = Campaign::$plugin->tracker->unsubscribe($contact, $sendout);
        }

        if (Craft::$app->getRequest()->getAcceptsJson()) {
            return $this->asJson(['success' => true]);
        }

        // Get template
        $template = $mailingList !== null ? $mailingList->getMailingListType()->unsubscribeSuccessTemplate : '';

        // Use message template if none was defined
        if ($template == '') {
            $template = 'campaign/message';

            // Set template mode to CP
            Craft::$app->getView()->setTemplateMode(View::TEMPLATE_MODE_CP);
        }

        return $this->renderTemplate($template, [
            'title' => Craft::t('campaign', 'Unsubscribed'),
            'message' => Craft::t('campaign', 'You have successfully unsubscribed from the mailing list.'),
            'mailingList' => $mailingList,
        ]);
    }

    /**
     * Verifies a contact's email
     *
     * @return Response
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws NotFoundHttpException
     * @throws Throwable
     */
    public function actionVerifyEmail(): Response
    {
        // Get pending contact ID
        $pid = Craft::$app->getRequest()->getParam('pid');

        if ($pid === null) {
            throw new NotFoundHttpException(Craft::t('campaign', 'Invalid verification link.'));
        }

        // Verify pending contact
        $pendingContact = Campaign::$plugin->contacts->verifyPendingContact($pid);

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

        // Track subscribe
        Campaign::$plugin->tracker->subscribe($contact, $mailingList, 'web', $pendingContact->source, true);

        // Get template
        $template = $mailingList !== null ? $mailingList->getMailingListType()->verifySuccessTemplate : '';

        // Use message template if none was defined
        if ($template == '') {
            $template = 'campaign/message';

            // Set template mode to CP
            Craft::$app->getView()->setTemplateMode(View::TEMPLATE_MODE_CP);
        }

        return $this->renderTemplate($template, [
            'title' => 'Verified',
            'message' => Craft::t('campaign', 'You have successfully verified your email address.'),
            'mailingList' => $mailingList,
            'contact' => $contact,
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

        // Get contact
        $contact = $this->_getContact();

        if ($contact === null) {
            throw new NotFoundHttpException(Craft::t('campaign', 'Contact not found.'));
        }

        $uid = $request->getBodyParam('uid');

        // Verify UID
        if ($uid === null || $contact->uid !== $uid) {
            throw new NotFoundHttpException(Craft::t('campaign', 'Contact could not be verified.'));
        }

        // Set the field values using the fields location
        $fieldsLocation = $request->getParam('fieldsLocation', 'fields');
        $contact->setFieldValuesFromRequest($fieldsLocation);

        // Save it
        if (!Campaign::$plugin->tracker->updateContact($contact)) {
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

    // Private Methods
    // =========================================================================

    /**
     * Gets contact by CID in param
     *
     * @return ContactElement|null
     */
    private function _getContact()
    {
        $cid = Craft::$app->getRequest()->getParam('cid');

        if ($cid === null) {
            return null;
        }

        return Campaign::$plugin->contacts->getContactByCid($cid);
    }

    /**
     * Gets sendout by SID in param
     *
     * @return SendoutElement|null
     */
    private function _getSendout()
    {
        $sid = Craft::$app->getRequest()->getParam('sid');

        if ($sid === null) {
            return null;
        }

        return Campaign::$plugin->sendouts->getSendoutBySid($sid);
    }

    /**
     * Gets link by LID in param
     *
     * @return LinkRecord|null
     */
    private function _getLink()
    {
        $lid = Craft::$app->getRequest()->getParam('lid');

        if ($lid === null) {
            return null;
        }

        return LinkRecord::findOne(['lid' => $lid]);
    }
}
