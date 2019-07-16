<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\controllers;

use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\SendoutElement;
use putyourlightson\campaign\records\LinkRecord;

use Craft;
use craft\errors\ElementNotFoundException;
use craft\web\Controller;
use craft\web\View;
use Throwable;
use yii\base\Exception;
use yii\base\InvalidConfigException;
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
    protected $allowAnonymous = true;

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
     * Subscribe
     *
     * @deprecated in 1.10.0. Use [[FormsController::actionSubscribeContact()]] instead.
     */
    public function actionSubscribe()
    {
        Craft::$app->getDeprecator()->log('TrackerController::actionSubscribe()', 'The “campaign/tracker/subscribe” controller action has been deprecated. Use “campaign/forms/subscribe-contact” instead.');

        return Craft::$app->runAction('campaign/forms/subscribe-contact');
    }

    /**
     * Updates a contact
     *
     * @deprecated in 1.10.0. Use [[FormsController::actionUpdateContact()]] instead.
     */
    public function actionUpdateContact()
    {
        Craft::$app->getDeprecator()->log('TrackerController::actionUpdateContact()', 'The “campaign/tracker/update-contact” controller action has been deprecated. Use “campaign/forms/update-contact” instead.');

        return Craft::$app->runAction('campaign/forms/update-contact');
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
