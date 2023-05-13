<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\controllers;

use Craft;
use putyourlightson\campaign\base\BaseMessageController;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\SendoutElement;
use putyourlightson\campaign\records\LinkRecord;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class TrackerController extends BaseMessageController
{
    /**
     * @inheritdoc
     */
    protected int|bool|array $allowAnonymous = true;

    /**
     * Tracks an open.
     */
    public function actionOpen(): ?Response
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

        return $this->response->sendFile($filePath);
    }

    /**
     * Tracks a click.
     */
    public function actionClick(): ?Response
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

                // Split the URL on the anchor hashtag, so we can add it at the end.
                // https://github.com/putyourlightson/craft-campaign/issues/383
                $urlParts = explode( "#", $url);
                $url = $urlParts[0];
                $hashtag = !empty($urlParts[1]) ? '#' . $urlParts[1] :  '';

                $url .= str_contains($url, '?') ? '&' : '?';
                $url .= trim($queryStringParameters, '?&');
                $url .= $hashtag;
            }
        }

        // Redirect to URL
        return $this->redirect($url);
    }

    /**
     * Tracks an unsubscribe.
     */
    public function actionUnsubscribe(): ?Response
    {
        if ($this->request->getParam('sid') === null) {
            throw new NotFoundHttpException(Craft::t('campaign', 'Unsubscribe link clicked in a test email without a sendout.'));
        }

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

        if ($this->request->getAcceptsJson()) {
            return $this->asJson(['success' => true]);
        }

        return $this->renderMessageTemplate($mailingList->getMailingListType()->unsubscribeSuccessTemplate, [
            'title' => Craft::t('campaign', 'Unsubscribed'),
            'message' => Craft::t('campaign', 'You have successfully unsubscribed from the mailing list.'),
            'mailingList' => $mailingList,
        ]);
    }

    /**
     * Gets a contact by CID in param.
     */
    private function _getContact(): ?ContactElement
    {
        $cid = $this->request->getParam('cid');

        if ($cid === null) {
            return null;
        }

        return Campaign::$plugin->contacts->getContactByCid($cid);
    }

    /**
     * Gets a sendout by SID in param.
     */
    private function _getSendout(): ?SendoutElement
    {
        $sid = $this->request->getParam('sid');

        if ($sid === null) {
            return null;
        }

        return Campaign::$plugin->sendouts->getSendoutBySid($sid);
    }

    /**
     * Gets a link by LID in param.
     */
    private function _getLink(): ?LinkRecord
    {
        $lid = $this->request->getParam('lid');

        if ($lid === null) {
            return null;
        }

        return LinkRecord::findOne(['lid' => $lid]);
    }
}
