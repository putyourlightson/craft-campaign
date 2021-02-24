<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\console\controllers;

use Craft;
use craft\helpers\Console;
use putyourlightson\campaign\Campaign;
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
     * Queues pending sendouts.
     *
     * @return int
     */
    public function actionQueue(): int
    {
        $count = Campaign::$plugin->sendouts->queuePendingSendouts();

        $this->stdout(Craft::t('campaign', '{count} pending sendout(s) queued.', ['count' => $count]).PHP_EOL, Console::FG_GREEN);

        return ExitCode::OK;
    }

    /**
     * Runs pending sendouts.
     *
     * @return int
     */
    public function actionRun(): int
    {
        $this->actionQueue();

        Craft::$app->getQueue()->run();

        return ExitCode::OK;
    }

    /**
     * Runs pending sendouts (deprecated).
     *
     * @return int
     * @deprecated in 1.18.1. Use [[campaign/sendouts/run]] instead.
     */
    public function actionRunPendingSendouts(): int
    {
        Craft::$app->getDeprecator()->log('campaign/sendouts/run-pending-sendouts', 'The “campaign/sendouts/run-pending-sendouts” console command has been deprecated. Use “campaign/sendouts/run” instead.');

        $this->actionRun();

        return ExitCode::OK;
    }
}
