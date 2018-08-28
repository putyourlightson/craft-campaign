<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\controllers;

use craft\helpers\DateTimeHelper;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\CampaignElement;
use putyourlightson\campaign\elements\MailingListElement;

use Craft;
use craft\web\Controller;
use putyourlightson\campaign\services\ReportsService;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
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
     * @inheritdoc
     * @throws ForbiddenHttpException
     */
    public function init()
    {
        // Require permission
        $this->requirePermission('campaign:reports');
    }

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

        $chart = $this->_getChartData($data, $interval);

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

        $chart = $this->_getChartData($data, $interval);

        return $this->asJson($chart);
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns chart data
     *
     * @param array $data
     * @param string $interval
     *
     * @return array
     */
    private function _getChartData(array $data, string $interval): array
    {
        $chart = [];

        // Get timestamps
        $timestamps = [];

        /** @var \DateTime $dateTime */
        $dateTime = $data['startDateTime'];
        $now = new \DateTime();

        for ($i = 0; $i < ReportsService::MAX_INTERVALS; $i++) {
            // Convert dateTime to format and then timestamp
            $timestamps[] = DateTimeHelper::toDateTime($dateTime->format($data['format']))->getTimestamp();

            // Break loop if datetime is in the future or after last interaction and loop index is at least the min intervals
            if ($dateTime > $now OR ($data['lastInteraction'] !== null AND $dateTime > $data['lastInteraction'] AND $i >= ReportsService::MIN_INTERVALS )) {
                break;
            }

            $dateTime->modify('+1 '.$data['interval']);
        }

        $chart['series'] = [];
        $chart['maxValue'] = 0;

        foreach ($data['interactions'] as $interaction) {
            $values = [];

            foreach ($timestamps as $timestamp) {
                $value = $data['activity'][$interaction][$timestamp] ?? 0;

                // Convert timestamp to milliseconds
                $values[] = [$timestamp * 1000, $value];

                $chart['maxValue'] = $value > $chart['maxValue'] ? $value : $chart['maxValue'];
            }

            $chart['series'][] = [
                'name' => Craft::t('campaign', ucfirst($interaction)),
                'data' => $values,
            ];
        }

        // Get colors
        $chart['colors'] = $this->_getColors($data['interactions']);

        // Get interval and locale
        $chart['interval'] = $interval;
        $chart['locale'] = Craft::$app->getLocale()->id;

        return $chart;
    }

    /**
     * Returns colors
     *
     * @param array $interactions
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