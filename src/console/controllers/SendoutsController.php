<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\console\controllers;

use craft\helpers\Console;
use putyourlightson\campaign\Campaign;
use Craft;
use yii\console\Controller;

/**
 * SendoutsController
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.3.0
 */
class SendoutsController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * Run pending sendouts
     *
     * @throws \Throwable
     */
    public function actionRunPendingSendouts()
    {
        $count = Campaign::$plugin->sendouts->queuePendingSendouts();

        $queue = Craft::$app->getQueue()->run();

        $this->stdout(Craft::t('campaign', '{count} pending sendout(s) queued.', ['count' => $count]).PHP_EOL, Console::FG_GREEN);
    }
}