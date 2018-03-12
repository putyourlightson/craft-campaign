<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\controllers;

use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\CampaignElement;
use putyourlightson\campaign\elements\MailingListElement;

use Craft;
use craft\web\Controller;
use yii\web\BadRequestHttpException;
use yii\web\Response;

/**
 * ReportsController
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 */
class ReportsController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * Returns campaigns chart data
     *
     * @return Response|null
     * @throws BadRequestHttpException
     */
    public function actionGetCampaignsChartData()
    {
        $this->requireAcceptsJson();

        $chart = [];

        // Get chart data
        $data = Campaign::$plugin->reports->getCampaignsChartData();

        // Set labels
        /** @var CampaignElement[] $data */
        foreach ($data['campaigns'] as $campaign) {
            $chart['data']['labels'][] = $campaign->title;
        }

        // Add recipients to interactions
        $data['interactions'] = array_merge(['recipients'], $data['interactions']);

        // Get datasets
        foreach ($data['interactions'] as $interaction) {
            $values = [];

            // Set values
            foreach ($data['campaigns'] as $campaign) {
                $values[] = $campaign->$interaction;
            }

            $chart['data']['datasets'][] = [
                'title' => Craft::t('campaign', ucfirst($interaction)),
                'values' => $values,
            ];
        }

        // Get colors
        $chart['colors'] = $this->_getColors($data['interactions']);

        return $this->asJson($chart);
    }

    /**
     * Returns campaign chart data
     *
     * @return Response|null
     * @throws BadRequestHttpException
     */
    public function actionGetCampaignChartData()
    {
        $this->requireAcceptsJson();

        $campaignId = Craft::$app->getRequest()->getRequiredParam('campaignId');
        $interval = Craft::$app->getRequest()->getParam('interval');

        // Get chart data
        $data = Campaign::$plugin->reports->getCampaignChartData($campaignId, $interval);

        /** @var \DateTime $dateTime */
        $dateTime = $data['startDateTime'];
        $now = new \DateTime();

        // Set chart title
        $chart['title'] = Craft::t('campaign', 'Campaign first sent on {date}', ['date' => $dateTime->format(Craft::$app->getLocale()->getDateTimeFormat('full', 'php'))]);

        // Set chart type
        $chart['type'] = 'line';


        $chart['data']['labels'] = [];
        $chart['data']['indexes'] = [];

        // Get labels and indexes
        for ($i = 0; $i < 12; $i++) {
            $label = $dateTime->format($data['format']['label']);
            $chart['data']['labels'][] = $label;
            $chart['data']['indexes'][$label] = $dateTime->format($data['format']['index']);

            if ($dateTime > $now) {
                break;
            }
            $dateTime->modify('+1 '.$data['interval']);
        }

        // Get datasets
        /** @var string[][] $data */
        foreach ($data['interactions'] as $interaction) {
            $values = [];

            // Set values
            /** @var string[][][][] $chart */
            foreach ($chart['data']['indexes'] as $index) {
                $values[] = $data['activity'][$interaction][$index] ?? 0;
            }

            $chart['data']['datasets'][] = [
                'title' => Craft::t('campaign', ucfirst($interaction)),
                'values' => $values,
            ];
        }

        // Get colors
        $chart['colors'] = $this->_getColors($data['interactions']);

        return $this->asJson($chart);
    }

    /**
     * Returns mailing lists chart data
     *
     * @return Response|null
     * @throws BadRequestHttpException
     */
    public function actionGetMailingListsChartData()
    {
        $this->requireAcceptsJson();

        // Get chart data
        $data = Campaign::$plugin->reports->getMailingListsChartData();

        // Set labels
        /** @var MailingListElement[] $data */
        foreach ($data['mailingLists'] as $mailingList) {
            $chart['data']['labels'][] = $mailingList->title;
        }

        // Get datasets
        foreach ($data['interactions'] as $interaction) {
            $values = [];

            // Set values
            foreach ($data['mailingLists'] as $mailingList) {
                $count = $interaction.'Count';
                $values[] = $mailingList->$count;
            }

            $chart['data']['datasets'][] = [
                'title' => Craft::t('campaign', ucfirst($interaction)),
                'values' => $values,
            ];
        }

        // Get colors
        /** @var string[][] $data['interactions'] */
        $chart['colors'] = $this->_getColors($data['interactions']);

        return $this->asJson($chart);
    }

    /**
     * Returns mailing list chart data
     *
     * @return Response|null
     * @throws BadRequestHttpException
     */
    public function actionGetMailingListChartData()
    {
        $this->requireAcceptsJson();

        $mailingListId = Craft::$app->getRequest()->getRequiredParam('mailingListId');
        $interval = Craft::$app->getRequest()->getParam('interval');

        // Get chart data
        $data = Campaign::$plugin->reports->getMailingListChartData($mailingListId, $interval);

        /** @var \DateTime $dateTime */
        $dateTime = $data['startDateTime'];
        $now = new \DateTime();

        // Set chart title
        $chart['title'] = Craft::t('campaign', 'Mailing list created on {date}', ['date' => $dateTime->format(Craft::$app->getLocale()->getDateTimeFormat('full', 'php'))]);

        // Set chart type
        $chart['type'] = 'line';

        // Get labels and indexes
        for ($i = 0; $i < 12; $i++) {
            $label = $dateTime->format($data['format']['label']);
            $chart['data']['labels'][] = $label;
            $chart['data']['indexes'][$label] = $dateTime->format($data['format']['index']);

            if ($dateTime > $now) {
                break;
            }
            $dateTime->modify('+1 '.$data['interval']);
        }

        // Get datasets
        /** @var string[][] $data */
        foreach ($data['interactions'] as $interaction) {
            $values = [];

            // Set values
            /** @var string[][][][] $chart */
            foreach ($chart['data']['indexes'] as $index) {
                $values[] = $data['activity'][$interaction][$index] ?? 0;
            }

            $chart['data']['datasets'][] = [
                'title' => Craft::t('campaign', ucfirst($interaction)),
                'values' => $values,
            ];
        }

        // Get colors
        $chart['colors'] = $this->_getColors($data['interactions']);

        return $this->asJson($chart);
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns colors
     *
     * @param []
     *
     * @return array
     */
    private function _getColors(array $interactions): array
    {
        $allColors = [
            'recipients' => '#E3E5E8',
            'opened' => '#4486EC',
            'clicked' => '#008000',
            'subscribed' => '#008000',
            'unsubscribed' => '#D0021B',
            'complained' => '#503112',
            'bounced' => '#000000',
        ];

        $colors = [];

        foreach ($interactions as $interaction) {
            if (isset($allColors[$interaction])) {
                $colors[] = $allColors[$interaction];
            }
        }

        return $colors;
    }
}