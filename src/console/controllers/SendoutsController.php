<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\console\controllers;

use Craft;
use craft\helpers\Console;
use putyourlightson\campaign\Campaign;
use Throwable;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Allows you to run pending sendouts.
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
     * Runs pending sendouts.
     *
     * @return int
     * @throws Throwable
     */
    public function actionRunPendingSendouts(): int
    {
        $count = Campaign::$plugin->sendouts->queuePendingSendouts();

        Craft::$app->getQueue()->run();

        $this->stdout(Craft::t('campaign', '{count} pending sendout(s) queued.', ['count' => $count]).PHP_EOL, Console::FG_GREEN);

        return ExitCode::OK;
    }
}
