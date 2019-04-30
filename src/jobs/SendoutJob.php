<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\jobs;

use Exception;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\SendoutElement;
use putyourlightson\campaign\events\SendoutEvent;
use putyourlightson\campaign\records\SendoutRecord;
use putyourlightson\campaign\services\SendoutsService;

use Craft;
use craft\queue\BaseJob;
use Throwable;
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

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getTtr()
    {
        // Call for max power
        Campaign::$plugin->maxPowerLieutenant();

        // Get time limit
        $timeLimit = (int)ini_get('max_execution_time');
        $timeLimit = $timeLimit == 0 ? Campaign::$plugin->getSettings()->unlimitedTimeLimit : $timeLimit;

        return $timeLimit;
    }

    /**
     * @inheritdoc
     */
    public function canRetry($attempt, $error): bool
    {
        return $attempt < Campaign::$plugin->getSettings()->maxRetryAttempts;
    }

    /**
     * @inheritdoc
     * @return void
     * @throws Exception
     * @throws Throwable
     */
    public function execute($queue)
    {
        // Get sendout
        $sendout = Campaign::$plugin->sendouts->getSendoutById($this->sendoutId);

        if ($sendout === null) {
            return;
        }

        // Ensure sendout is sendable
        if (!$sendout->getIsSendable()) {
            return;
        }

        // Fire a before event
        $event = new SendoutEvent([
            'sendout' => $sendout,
        ]);
        Campaign::$plugin->sendouts->trigger(SendoutsService::EVENT_BEFORE_SEND, $event);

        if (!$event->isValid) {
            return null;
        }

        // Call for max power
        Campaign::$plugin->maxPowerLieutenant();

        // Get settings
        $settings = Campaign::$plugin->getSettings();

        // Get memory limit with threshold if unlimited
        $memoryLimit = ini_get('memory_limit');
        $memoryLimit = $memoryLimit == -1 ? $this->_memoryInBytes($settings->unlimitedMemoryLimit) : round($this->_memoryInBytes($memoryLimit) * $settings->memoryThreshold);

        // Get time limit with threshold if unlimited
        $timeLimit = ini_get('max_execution_time');
        $timeLimit = $timeLimit == 0 ? $settings->unlimitedTimeLimit : round($timeLimit * $settings->timeThreshold);

        // Set the current site from the sendout's site ID
        Craft::$app->sites->setCurrentSite($sendout->siteId);

        // Prepare sending
        Campaign::$plugin->sendouts->prepareSending($sendout);

        // Get pending recipients
        $pendingRecipients = $sendout->getPendingRecipients();

        $count = 0;
        $total = count($pendingRecipients);
        $batchSize = min($total, $settings->maxBatchSize);

        // Loop as long as the there are pending recipients
        while (count($pendingRecipients)) {
            // Set progress
            $this->setProgress($queue, $count / $batchSize);

            // Get next pending recipient
            $pendingRecipient = array_shift($pendingRecipients);

            $contact = Campaign::$plugin->contacts->getContactById($pendingRecipient['contactId']);

            if ($contact === null) {
                continue;
            }

            $mailingListId = $pendingRecipient['mailingListId'];

            // Send email
            Campaign::$plugin->sendouts->sendEmail($sendout, $contact, $mailingListId);

            // Increment count
            $count++;

            // If we're beyond the memory limit or time limit or max batch size has been reached
            if (memory_get_usage() > $memoryLimit || time() - $_SERVER['REQUEST_TIME'] > $timeLimit || $count >= $batchSize) {
                // Add new job to queue with delay
                Craft::$app->getQueue()->delay($settings->batchJobDelay)->push(new self([
                    'sendoutId' => $this->sendoutId,
                    'title' => $this->title,
                    'batch' => $this->batch + 1,
                ]));

                return null;
            }

            /** @var SendoutRecord|null $sendoutRecord */
            $sendoutRecord = SendoutRecord::findOne($sendout->id);

            // Ensure sendout record still exists and is sendable (it may have been deleted or its send status changed in the meantime)
            if ($sendoutRecord === null OR $sendoutRecord->sendStatus !== SendoutElement::STATUS_SENDING) {
                return null;
            }
        }

        // Finalise sending
        Campaign::$plugin->sendouts->finaliseSending($sendout);

        // Fire an after event
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
        $value = (int)$value;
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
        return Craft::t('campaign', 'Sending “{title}” sendout [batch {batch}]', [
            'title' => $this->title,
            'batch' => $this->batch,
        ]);
    }
}
