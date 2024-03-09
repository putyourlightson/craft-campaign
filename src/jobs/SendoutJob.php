<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\jobs;

use Craft;
use craft\queue\BaseBatchedJob;
use putyourlightson\campaign\batchers\SendoutBatcher;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\SendoutElement;
use putyourlightson\campaign\events\SendoutEvent;
use putyourlightson\campaign\services\SendoutsService;
use yii\queue\RetryableJobInterface;

class SendoutJob extends BaseBatchedJob implements RetryableJobInterface
{
    /**
     * @var int
     */
    public int $sendoutId;

    /**
     * @var string|null
     */
    public ?string $title = null;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        $this->batchSize = Campaign::$plugin->settings->maxBatchSize;

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function getTtr(): int
    {
        return Campaign::$plugin->settings->sendoutJobTtr;
    }

    /**
     * @inheritdoc
     */
    public function canRetry($attempt, $error): bool
    {
        return $attempt < Campaign::$plugin->settings->maxRetryAttempts;
    }

    /**
     * @inheritdoc
     */
    public function execute($queue): void
    {
        $sendout = $this->getCurrentSendout();
        if ($sendout === null || !$sendout->getIsSendable()) {
            return;
        }

        $event = new SendoutEvent([
            'sendout' => $sendout,
        ]);
        Campaign::$plugin->sendouts->trigger(SendoutsService::EVENT_BEFORE_SEND, $event);

        if (!$event->isValid) {
            return;
        }

        parent::execute($queue);
    }

    /**
     * @inheritdoc
     */
    protected function beforeBatch(): void
    {
        Campaign::$plugin->sendouts->prepareSending($this->getCurrentSendout(), $this->batchIndex + 1);

        if ($this->batchIndex > 0) {
            $batchJobDelay = Campaign::$plugin->settings->batchJobDelay;
            if ($batchJobDelay > 0) {
                sleep(Campaign::$plugin->settings->batchJobDelay);
            }
        }
    }

    /**
     * @inheritdoc
     */
    protected function after(): void
    {
        $sendout = $this->getCurrentSendout();
        if ($sendout === null || !$sendout->getIsSendable()) {
            return;
        }

        Campaign::$plugin->sendouts->finaliseSending($sendout);

        if (Campaign::$plugin->sendouts->hasEventHandlers(SendoutsService::EVENT_AFTER_SEND)) {
            Campaign::$plugin->sendouts->trigger(SendoutsService::EVENT_AFTER_SEND, new SendoutEvent([
                'sendout' => $sendout,
            ]));
        }
    }

    /**
     * @inheritdoc
     */
    protected function loadData(): SendoutBatcher
    {
        $sendout = $this->getCurrentSendout();
        if ($sendout === null || !$sendout->getIsSendable()) {
            return new SendoutBatcher(null);
        }

        return new SendoutBatcher($sendout);
    }

    /**
     * @inheritdoc
     */
    protected function processItem(mixed $item): void
    {
        // Ensure the send status is still `sending`, as it may have changed.
        $sendout = $this->getCurrentSendout();
        if ($sendout->sendStatus !== SendoutElement::STATUS_SENDING) {
            return;
        }

        $contact = Campaign::$plugin->contacts->getContactById($item['contactId']);

        if ($contact === null) {
            return;
        }

        Campaign::$plugin->sendouts->sendEmail($sendout, $contact, $item['mailingListId']);
    }

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): string
    {
        return Craft::t('campaign', 'Sending “{title}” sendout', [
            'title' => $this->title,
        ]);
    }

    /**
     * Returns a fresh version of the current sendout.
     */
    private function getCurrentSendout(): ?SendoutElement
    {
        return Campaign::$plugin->sendouts->getSendoutById($this->sendoutId);
    }
}
