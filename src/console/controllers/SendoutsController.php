<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\console\controllers;

use Craft;
use craft\queue\Queue;
use putyourlightson\campaign\Campaign;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\BaseConsole;

/**
 * Allows you to run pending sendouts.
 *
 * @since 1.3.0
 */
class SendoutsController extends Controller
{
    /**
     * Queues pending sendouts.
     */
    public function actionQueue(): int
    {
        $this->_queuePendingSendouts();

        return ExitCode::OK;
    }

    /**
     * Runs pending sendouts.
     */
    public function actionRun(): int
    {
        $this->_queuePendingSendouts();

        /** @var Queue $queue */
        $queue = Craft::$app->getQueue();
        $queue->run();

        return ExitCode::OK;
    }

    private function _queuePendingSendouts(): void
    {
        $count = Campaign::$plugin->sendouts->queuePendingSendouts();

        $this->stdout(Craft::t('campaign', '{count} pending sendout(s) queued.', ['count' => $count]) . PHP_EOL, BaseConsole::FG_GREEN);
    }
}
