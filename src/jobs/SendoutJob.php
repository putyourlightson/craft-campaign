<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\jobs;

use Craft;
use craft\queue\BaseJob;
use Exception;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\SendoutElement;
use putyourlightson\campaign\events\SendoutEvent;
use putyourlightson\campaign\helpers\SendoutHelper;
use putyourlightson\campaign\services\SendoutsService;
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
        return Campaign::$plugin->getSettings()->sendoutJobTtr;
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
        $memoryLimit = $memoryLimit == -1 ? SendoutHelper::memoryInBytes($settings->unlimitedMemoryLimit) : round(SendoutHelper::memoryInBytes($memoryLimit) * $settings->memoryThreshold);

        // Get time limit with threshold if unlimited
        $timeLimit = ini_get('max_execution_time');
        $timeLimit = $timeLimit == 0 ? $settings->unlimitedTimeLimit : round($timeLimit * $settings->timeThreshold);

        // Prepare sending
        Campaign::$plugin->sendouts->prepareSending($sendout);

        // Get pending recipients
        $pendingRecipients = $sendout->getPendingRecipients();

        $count = 0;
        $batchSize = min(count($pendingRecipients), $settings->maxBatchSize);

        foreach ($pendingRecipients as $pendingRecipient) {
            $count++;
            $this->setProgress($queue, $count / $batchSize);

            $contact = Campaign::$plugin->contacts->getContactById($pendingRecipient['contactId']);

            if ($contact === null) {
                continue;
            }

            // Send email
            Campaign::$plugin->sendouts->sendEmail($sendout, $contact, $pendingRecipient['mailingListId']);

            // If we're beyond the memory limit or time limit or max batch size has been reached
            if (memory_get_usage(true) > $memoryLimit || time() - $_SERVER['REQUEST_TIME'] > $timeLimit || $count >= $batchSize) {
                // Add new job to queue with delay
                Craft::$app->getQueue()->delay($settings->batchJobDelay)->push(new self([
                    'sendoutId' => $this->sendoutId,
                    'title' => $this->title,
                    'batch' => $this->batch + 1,
                ]));

                return null;
            }

            // Ensure sendout send status is still sending as it may have had its status changed
            $sendoutSendStatus = Campaign::$plugin->sendouts->getSendoutSendStatusById($sendout->id);

            if ($sendoutSendStatus !== SendoutElement::STATUS_SENDING) {
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
