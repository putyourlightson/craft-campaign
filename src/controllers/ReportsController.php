<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\controllers;

use Craft;
use craft\helpers\DateTimeHelper;
use craft\web\Controller;

use DateTime;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\services\ReportsService;
use yii\web\Response;

class ReportsController extends Controller
{
    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        // Require permission
        $this->requirePermission('campaign:reports');

        return parent::beforeAction($action);
    }

    /**
     * Returns campaign chart data.
     */
    public function actionGetCampaignChartData(): ?Response
    {
        $this->requireAcceptsJson();

        $campaignId = $this->request->getRequiredParam('campaignId');
        $interval = $this->request->getParam('interval');

        // Get chart data
        $data = Campaign::$plugin->reports->getCampaignChartData($campaignId, $interval);
        $chart = $this->_getChartData($data, $interval);

        return $this->asJson($chart);
    }

    /**
     * Returns mailing list chart data.
     */
    public function actionGetMailingListChartData(): ?Response
    {
        $this->requireAcceptsJson();

        $mailingListId = $this->request->getRequiredParam('mailingListId');
        $interval = $this->request->getParam('interval');

        // Get chart data
        $data = Campaign::$plugin->reports->getMailingListChartData($mailingListId, $interval);
        $chart = $this->_getChartData($data, $interval);

        return $this->asJson($chart);
    }

    /**
     * Returns chart data.
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

            $dateTime->modify('+1 ' . $data['interval']);
        }

        $chart['series'] = [];
        $chart['maxValue'] = 0;

        foreach ($data['interactions'] as $interaction) {
            $values = [];

            foreach ($timestamps as $timestamp) {
                $value = $data['activity'][$interaction][$timestamp] ?? 0;

                // Convert timestamp to milliseconds
                $values[] = [$timestamp * 1000, $value];

                $chart['maxValue'] = max($value, $chart['maxValue']);
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
     * Returns colors.
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
            'blocked' => '#000000',
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
