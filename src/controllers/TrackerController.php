<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\controllers;

use craft\errors\DeprecationException;
use putyourlightson\campaign\base\BaseMessageController;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\SendoutElement;
use putyourlightson\campaign\records\LinkRecord;

use Craft;
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
class TrackerController extends BaseMessageController
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
     * @throws Throwable
     */
    public function actionOpen()
    {
        // Get contact and sendout
        $contact = $this->_getContact();
        $sendout = $this->_getSendout();

        if ($contact && $sendout) {
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

        if ($contact && $sendout) {
            // Track click
            Campaign::$plugin->tracker->click($contact, $sendout, $linkRecord);

            // Add query string parameters if not empty
            $queryStringParameters = $sendout->getCampaign()->getCampaignType()->queryStringParameters;

            if (!empty($queryStringParameters)) {
                $view = Craft::$app->getView();
                $queryStringParameters = $view->renderString($queryStringParameters, [
                    'sendout' => $sendout,
                    'campaign' => $sendout->getCampaign(),
                ]);

                $url .= strpos($url, '?') === false ? '?' : '&';
                $url .= trim($queryStringParameters, '?&');
            }
        }

        // Redirect to URL
        return $this->redirect($url);
    }

    /**
     * Unsubscribe
     *
     * @return Response|null
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function actionUnsubscribe()
    {
        // Get contact and sendout
        $contact = $this->_getContact();
        $sendout = $this->_getSendout();

        if ($contact === null || $sendout === null) {
            throw new NotFoundHttpException(Craft::t('campaign', 'Unsubscribe link is invalid.'));
        }

        // Track unsubscribe
        $mailingList = Campaign::$plugin->tracker->unsubscribe($contact, $sendout);

        if ($mailingList === null) {
            throw new NotFoundHttpException(Craft::t('campaign', 'Unsubscribe link is invalid.'));
        }

        if (Craft::$app->getRequest()->getAcceptsJson()) {
            return $this->asJson(['success' => true]);
        }

        return $this->renderMessageTemplate($mailingList->getMailingListType()->unsubscribeSuccessTemplate, [
            'title' => Craft::t('campaign', 'Unsubscribed'),
            'message' => Craft::t('campaign', 'You have successfully unsubscribed from the mailing list.'),
            'mailingList' => $mailingList,
        ]);
    }

    /**
     * Subscribe
     *
     * @throws DeprecationException
     * @deprecated in 1.10.0. Use [[FormsController::actionSubscribe()]] instead.
     */
    public function actionSubscribe()
    {
        Craft::$app->getDeprecator()->log('TrackerController::actionSubscribe()', 'The “campaign/tracker/subscribe” controller action has been deprecated. Use “campaign/forms/subscribe” instead.');

        return Campaign::$plugin->runAction('forms/subscribe');
    }

    /**
     * Updates a contact
     *
     * @deprecated in 1.10.0. Use [[FormsController::actionUpdateContact()]] instead.
     */
    public function actionUpdateContact()
    {
        Craft::$app->getDeprecator()->log('TrackerController::actionUpdateContact()', 'The “campaign/tracker/update-contact” controller action has been deprecated. Use “campaign/forms/update-contact” instead.');

        return Campaign::$plugin->runAction('forms/update-contact');
    }

    /**
     * Verifies a contact's email
     *
     * @deprecated in 1.10.0. Use [[FormsController::actionVerifySubscribe()]] instead.
     */
    public function actionVerifyEmail()
    {
        Craft::$app->getDeprecator()->log('TrackerController::actionVerifyEmail()', 'The “campaign/tracker/update-contact” controller action has been deprecated. Use “campaign/forms/verify-subscribe” instead.');

        return Campaign::$plugin->runAction('forms/verify-subscribe');
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
