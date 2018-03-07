<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\jobs;

use putyourlightson\campaign\Campaign;

use Craft;
use craft\queue\BaseJob;
use putyourlightson\campaign\events\SendoutEvent;
use putyourlightson\campaign\services\SendoutsService;

/**
 * SendoutJob
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 */
class SendoutJob extends BaseJob
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

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     * @throws \Exception
     */
    public function execute($queue)
    {
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

        // Prepare sending
        Campaign::$plugin->sendouts->prepareSending($sendout);

        // Loop as long as the there are pending recipient IDs and the sendout is sendable
        while ($sendout->pendingRecipientIds AND $sendout->isSendable()) {
            // Set progress
            $progress = $sendout->getProgressFraction();
            $this->setProgress($queue, $progress);

            // Send email
            $sendout = Campaign::$plugin->sendouts->sendEmail($sendout);
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

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): string
    {
        return Craft::t('campaign', 'Sending “{title}” sendout.', [
            'title' => $this->title,
        ]);
    }
}