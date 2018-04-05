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
use craft\queue\BaseJob;
use yii\queue\RetryableJobInterface;

/**
 * SendoutJob
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 *
 * @property int $ttr
 */
class SendoutJob extends BaseJob implements RetryableJobInterface
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
     * @var int
     */
    public $batch = 1;

    /**
     * @var mixed
     */
    public $unlimitedMemoryLimit = '1G';

    /**
     * @var int
     */
    public $unlimitedTimeLimit = 3600;

    // Public Methods
    // =========================================================================

    public function getTtr()
    {
        // Call for max power
        Campaign::$plugin->maxPowerLieutenant();

        // Get settings
        $settings = Campaign::$plugin->getSettings();

        // Get time limit with threshold if unlimited
        $timeLimit = ini_get('max_execution_time');
        $timeLimit = $timeLimit === 0 ? $this->unlimitedTimeLimit : round($timeLimit * $settings->timeThreshold);

        return $timeLimit;
    }

    public function canRetry($attempt, $error): bool
    {
        // Get settings
        $settings = Campaign::$plugin->getSettings();

        return $attempt < $settings->maxRetryAttempts;
    }

    /**
     * @inheritdoc
     * @throws \Exception
     * @throws \Throwable
     */
    public function execute($queue)
    {
        // Get sendout
        $sendout = Campaign::$plugin->sendouts->getSendoutById($this->sendoutId);

        if ($sendout === null) {
            return;
        }

        // Fire a 'beforeSend' event
        $event = new SendoutEvent([
            'sendout' => $sendout,
        ]);
        Campaign::$plugin->sendouts->trigger(SendoutsService::EVENT_BEFORE_SEND, $event);

        if (!$event->isValid) {
            return;
        }

        // Call for max power
        Campaign::$plugin->maxPowerLieutenant();

        // Get settings
        $settings = Campaign::$plugin->getSettings();

        // Get memory limit with threshold if unlimited
        $memoryLimit = ini_get('memory_limit');
        $memoryLimit = $memoryLimit === -1 ? $this->_memoryInBytes($this->unlimitedMemoryLimit) : round($this->_memoryInBytes($memoryLimit) * $settings->memoryThreshold);

        // Get time limit with threshold if unlimited
        $timeLimit = ini_get('max_execution_time');
        $timeLimit = $timeLimit === 0 ? $this->unlimitedTimeLimit : round($timeLimit * $settings->timeThreshold);

        // Prepare sending
        Campaign::$plugin->sendouts->prepareSending($sendout);

        // Get pending recipients
        $pendingRecipients = $sendout->getPendingRecipients();

        $count = 0;
        $expectedRecipients = count($pendingRecipients);

        // Loop as long as the there are pending recipients and the sendout is sendable
        while (count($pendingRecipients) AND $sendout->isSendable()) {
            // Set progress
            $this->setProgress($queue, $count / $expectedRecipients);

            // Get next pending recipient
            $pendingRecipient = array_shift($pendingRecipients);

            $contact = Campaign::$plugin->contacts->getContactById($pendingRecipient['contactId']);
            $mailingListId = $pendingRecipient['mailingListId'];

            // Send email
            Campaign::$plugin->sendouts->sendEmail($sendout, $contact, $mailingListId);

            // Increment count
            $count++;

            // If we're beyond the memory limit or time limit or max batch size has been reached
            if (memory_get_usage() > $memoryLimit OR time() - $_SERVER['REQUEST_TIME'] > $timeLimit OR $count >= $settings->maxBatchSize) {
                // Add new job to queue with delay
                Craft::$app->queue->delay($settings->batchJobDelay)->push(new self([
                    'sendoutId' => $this->sendoutId,
                    'title' => $this->title,
                    'batch' => $this->batch + 1,
                ]));

                return;
            }

            // Get fresh version of sendout
            $sendout = Campaign::$plugin->sendouts->getSendoutById($this->sendoutId);
        }

        // Finalise sending
        Campaign::$plugin->sendouts->finaliseSending($sendout);

        // Fire an 'afterSend' event
        if (Campaign::$plugin->sendouts->hasEventHandlers(SendoutsService::EVENT_AFTER_SEND)) {
            Campaign::$plugin->sendouts->trigger(SendoutsService::EVENT_AFTER_SEND, new SendoutEvent([
                'sendout' => $sendout,
            ]));
        }
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns the provided memory converted to bytes
     *
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
                $value *= pow(1024, 3);
                break;
            case 'm':
                $value *= pow(1024, 2);
                break;
            case 'k':
                $value *= 1024;
                break;
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
        return Craft::t('campaign', 'Sending “{title}” sendout batch {batch}', [
            'title' => $this->title,
            'batch' => $this->batch,
        ]);
    }
}