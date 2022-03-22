<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\jobs;

use Craft;
use craft\queue\BaseJob;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\SendoutElement;
use putyourlightson\campaign\events\SendoutEvent;
use putyourlightson\campaign\helpers\SendoutHelper;
use putyourlightson\campaign\services\SendoutsService;
use yii\base\Exception;
use yii\queue\RetryableJobInterface;

/**
 * @property-read int $ttr
 */
class SendoutJob extends BaseJob implements RetryableJobInterface
{
    /**
     * @var int
     */
    public int $sendoutId;

    /**
     * @var string|null
     */
    public ?string $title;

    /**
     * @var int
     */
    public int $batch = 1;

    /**
     * @inheritdoc
     */
    public function getTtr(): int
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
     */
    public function execute($queue): void
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
            return;
        }

        // Call for max power
        Campaign::$plugin->maxPowerLieutenant();

        // Get settings
        $settings = Campaign::$plugin->getSettings();

        // Get memory limit or set to null if unlimited
        $memoryLimit = ini_get('memory_limit');
        $memoryLimit = ($memoryLimit == -1) ? null : round(SendoutHelper::memoryInBytes($memoryLimit) * $settings->memoryThreshold);

        // Get time limit or set to null if unlimited
        $timeLimit = ini_get('max_execution_time');
        $timeLimit = ($timeLimit == 0) ? null : round($timeLimit * $settings->timeThreshold);

        // Prepare sending
        Campaign::$plugin->sendouts->prepareSending($sendout);

        // Get pending recipients
        $pendingRecipients = $sendout->getPendingRecipients();

        $count = 0;
        $batchSize = min(count($pendingRecipients) + 1, $settings->maxBatchSize);

        foreach ($pendingRecipients as $pendingRecipient) {
            $this->setProgress($queue, $count / $batchSize);
            $count++;

            $contact = Campaign::$plugin->contacts->getContactById($pendingRecipient['contactId']);

            if ($contact === null) {
                continue;
            }

            // Send email
            Campaign::$plugin->sendouts->sendEmail($sendout, $contact, $pendingRecipient['mailingListId']);

            // If we're beyond the memory limit or time limit or max batch size has been reached
            if (($memoryLimit && memory_get_usage(true) > $memoryLimit)
                || ($timeLimit && time() - $_SERVER['REQUEST_TIME'] > $timeLimit)
                || $count >= $batchSize
            ) {
                // Add new job to queue with delay
                Craft::$app->getQueue()->delay($settings->batchJobDelay)->push(new self([
                    'sendoutId' => $this->sendoutId,
                    'title' => $this->title,
                    'batch' => $this->batch + 1,
                ]));

                return;
            }

            // Ensure sendout send status is still sending as it may have had its status changed
            $sendoutSendStatus = Campaign::$plugin->sendouts->getSendoutSendStatusById($sendout->id);

            if ($sendoutSendStatus !== SendoutElement::STATUS_SENDING) {
                break;
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
