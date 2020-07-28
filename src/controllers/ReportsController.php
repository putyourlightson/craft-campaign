<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\controllers;

use craft\helpers\DateTimeHelper;
use DateTime;
use putyourlightson\campaign\Campaign;

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
        parent::init();

        // Require permission
        $this->requirePermission('campaign:reports');
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

        /** @var DateTime $dateTime */
        $dateTime = $data['startDateTime'];
        $now = new DateTime();
        $maxIntervals = Campaign::$plugin->reports->getMaxIntervals($interval);

        for ($i = 0; $i < $maxIntervals; $i++) {
            // Convert dateTime to format and then timestamp
            $timestamps[] = DateTimeHelper::toDateTime($dateTime->format($data['format']))->getTimestamp();

            // Break loop if datetime is in the future or after last interaction and loop index is at least the min intervals
            if ($dateTime > $now || ($data['lastInteraction'] !== null && $dateTime > $data['lastInteraction'] && $i >= ReportsService::MIN_INTERVALS)) {
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
