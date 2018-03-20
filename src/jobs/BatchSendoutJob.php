<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\jobs;

use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\events\SendoutEvent;
use putyourlightson\campaign\services\SendoutsService;

use Craft;
use craft\helpers\App;
use craft\queue\BaseJob;

use Performance\Performance;

/**
 * BatchSendoutJob
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 */
class BatchSendoutJob extends BaseJob
{
    // Properties
    // =========================================================================

    /**
     * @var int
     */
    public $sendoutId;

    /**
     * @var string|null
     */
    public $title;

    /**
     * @var float
     */
    public $threshold = 0.8;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     * @throws \Exception
     */
    public function execute($queue)
    {
        Performance::point();

        // Get sendout
        $sendout = Campaign::$plugin->sendouts->getSendoutById($this->sendoutId);

        // Fire a 'beforeSend' event
        $event = new SendoutEvent([
            'sendout' => $sendout,
        ]);
        Campaign::$plugin->sendouts->trigger(SendoutsService::EVENT_BEFORE_SEND, $event);

        if (!$event->isValid) {
            return;
        }

        // Call for max power
        App::maxPowerCaptain();

        // Get memory limit (default to 128MB if unlimited)
        $memoryLimit = ini_get('memory_limit');
        $memoryLimit = $memoryLimit > -1 ? round($this->_memoryInBytes($memoryLimit) * $this->threshold) : (128 * 1024 * 1024);

        // Get time limit (default to 5 minutes if unlimited)
        $timeLimit = ini_get('max_execution_time');
        $timeLimit = $timeLimit > 0 ? round($timeLimit * $this->threshold) : (5 * 60);

        // Prepare sending
        Campaign::$plugin->sendouts->prepareSending($sendout);

        // Loop as long as the there are pending recipient IDs and the sendout is sendable
        while ($sendout->pendingRecipientIds AND $sendout->isSendable() ) {
            // Get memory usage and execution time
            $memoryUsage = memory_get_usage();
            $executionTime = time() - $_SERVER['REQUEST_TIME'];

            // If we're beyond the half way memory and time limits
            if ($memoryUsage > $memoryLimit OR $executionTime > $timeLimit) {
                // Add new job to queue with delay of 10 seconds
                Craft::$app->queue->delay(10)->push(new self([
                    'sendoutId' => $this->sendoutId,
                    'title' => $this->title,
                ]));

                return;
            }

            // Set progress
            $progress = $sendout->getProgressFraction();
            $this->setProgress($queue, $progress);

            // Send email
            $sendout = Campaign::$plugin->sendouts->sendEmail($sendout);

            // TEST CODE
            if ($sendout->recipients > 1000) {
                return;
            }
        }


        // Finalise sending
        Campaign::$plugin->sendouts->finaliseSending($sendout);

        // Fire an 'afterSend' event
        if (Campaign::$plugin->sendouts->hasEventHandlers(SendoutsService::EVENT_AFTER_SEND)) {
            Campaign::$plugin->sendouts->trigger(SendoutsService::EVENT_AFTER_SEND, new SendoutEvent([
                'sendout' => $sendout,
            ]));
        }

        $export = Performance::export(); // Only return export

        // Return all information
        print_r($export->toFile('performance-'.time().'.txt'));
    }

    // Private Methods
    // =========================================================================

    /**
     * @param string $value
     *
     * @return int
     */
    private function _memoryInBytes(string $value): int
    {
        $unit = strtolower(substr($value, -1, 1));
        $value = (int) $value;
        switch($unit) {
            case 'g':
                $value *= 1024;
                // no break (cumulative multiplier)
            case 'm':
                $value *= 1024;
                // no break (cumulative multiplier)
            case 'k':
                $value *= 1024;
        }

        return $value;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): string
    {
        return Craft::t('campaign', 'Sending “{title}” sendout', [
            'title' => $this->title,
        ]);
    }
}