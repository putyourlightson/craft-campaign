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
use Yii;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class TrackerController extends BaseMessageController
{
    /**
     * @inheritdoc
     */
    public $enableCsrfValidation = false;

    /**
     * @var bool Disable Snaptcha validation
     */
    public bool $enableSnaptchaValidation = false;

    /**
     * @inheritdoc
     */
    protected int|bool|array $allowAnonymous = true;

    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        if (
            in_array($action->id, ['unsubscribe', 'unsubscribe-all'], true) &&
            Campaign::$plugin->settings->requireUnsubscribeConfirmation &&
            $this->request->getIsPost()
        ) {
            $this->enableCsrfValidation = true;
        }

        return parent::beforeAction($action);
    }

    /**
     * Tracks an open.
     */
    public function actionOpen(): ?Response
    {
        // Get contact and sendout
        $contact = $this->getContact();
        $sendout = $this->getSendout();

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
        $contact = $this->getContact();
        $sendout = $this->getSendout();
        $linkRecord = $this->getLink();

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
                $urlParts = explode('#', $url);
                $url = $urlParts[0];
                $hashtag = !empty($urlParts[1]) ? '#' . $urlParts[1] : '';

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
        return $this->processUnsubscribe();
    }

    /**
     * Tracks an unsubscribe from all mailing lists.
     *
     * @since 3.9.0
     */
    public function actionUnsubscribeAll(): ?Response
    {
        return $this->processUnsubscribe(true);
    }

    /**
     * Tracks a one-click unsubscribe.
     * https://postmarkapp.com/support/article/1299-how-to-include-a-list-unsubscribe-header
     *
     * @since 2.15.0
     */
    public function actionOneClickUnsubscribe(): ?Response
    {
        // Ignore if a non-POST requests but don’t require it, since anti-spam tools may send GET requests.
        if (!$this->request->getIsPost()) {
            return $this->asRaw('');
        }

        // Get contact and sendout
        $contact = $this->getContact();
        $sendout = $this->getSendout();

        if ($contact === null || $sendout === null) {
            throw new NotFoundHttpException(Craft::t('campaign', 'Unsubscribe link is invalid.'));
        }

        Campaign::$plugin->tracker->unsubscribe($contact, $sendout);

        return $this->asRaw('OK');
    }

    /**
     * Processes an unsubscribe.
     */
    private function processUnsubscribe(bool $all = false): Response
    {
        if ($this->request->getParam('sid') === null) {
            throw new NotFoundHttpException(Craft::t('campaign', 'Unsubscribe link clicked in a test email without a sendout.'));
        }

        // Get contact and sendout
        $contact = $this->getContact();
        $sendout = $this->getSendout();

        if ($contact === null || $sendout === null) {
            throw new NotFoundHttpException(Craft::t('campaign', 'Unsubscribe link is invalid.'));
        }

        if (Campaign::$plugin->settings->requireUnsubscribeConfirmation) {
            if (!$this->request->getIsPost()) {
                if ($this->request->getAcceptsJson()) {
                    return $this->asJson([
                        'success' => false,
                        'confirmationRequired' => true,
                    ]);
                }

                return $this->renderMessageTemplate([
                    'title' => Craft::t('campaign', 'Confirm unsubscribe'),
                    'message' => $all ?
                        Craft::t('campaign', 'Are you sure you want to unsubscribe from all mailing lists?') :
                        Craft::t('campaign', 'Are you sure you want to unsubscribe?'),
                    'hasUnsubscribeForm' => true,
                    'contact' => $contact,
                    'sendout' => $sendout,
                ], Campaign::$plugin->settings->unsubscribeConfirmationTemplate);
            }

            if (!$this->request->validateCsrfToken()) {
                throw new BadRequestHttpException(Yii::t('yii', 'Unable to verify your data submission.'));
            }

            if ($this->request->getBodyParam('confirm') !== '1') {
                throw new NotFoundHttpException(Craft::t('campaign', 'Unsubscribe confirmation is invalid.'));
            }
        }

        // Track unsubscribe
        $mailingList = null;
        if ($all) {
            Campaign::$plugin->tracker->unsubscribeAll($contact, $sendout);
        } else {
            $mailingList = Campaign::$plugin->tracker->unsubscribe($contact, $sendout);
        }

        if ($this->request->getAcceptsJson()) {
            return $this->asJson(['success' => true]);
        }

        $unsubscribeSuccessTemplate = $all ?
            Campaign::$plugin->settings->unsubscribeAllSuccessTemplate :
            $mailingList?->getMailingListType()->unsubscribeSuccessTemplate;

        return $this->renderMessageTemplate([
            'title' => Craft::t('campaign', 'Unsubscribed'),
            'message' => $all ?
                Craft::t('campaign', 'You have successfully unsubscribed from all mailing lists.') :
                Craft::t('campaign', 'You have successfully unsubscribed from the mailing list.'),
            'mailingList' => $mailingList,
        ], $unsubscribeSuccessTemplate);
    }

    /**
     * Gets a contact by CID in param.
     */
    private function getContact(): ?ContactElement
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
    private function getSendout(): ?SendoutElement
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
    private function getLink(): ?LinkRecord
    {
        $lid = $this->request->getParam('lid');

        if ($lid === null) {
            return null;
        }

        return LinkRecord::findOne(['lid' => $lid]);
    }
}
