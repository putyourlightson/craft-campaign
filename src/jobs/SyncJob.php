<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\jobs;

use Craft;
use craft\elements\User;
use craft\queue\BaseJob;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\events\SyncEvent;
use putyourlightson\campaign\services\SyncService;
use yii\queue\RetryableJobInterface;

/**
 * @since 1.2.0
 */
class SyncJob extends BaseJob implements RetryableJobInterface
{
    /**
     * @var int
     */
    public int $mailingListId;

    /**
     * @inheritdoc
     */
    public function getTtr(): int
    {
        return Campaign::$plugin->settings->syncJobTtr;
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
        $mailingList = Campaign::$plugin->mailingLists->getMailingListById($this->mailingListId);

        if ($mailingList === null) {
            return;
        }

        if ($mailingList->syncedUserGroupId === null) {
            return;
        }

        // Fire a before event
        $event = new SyncEvent([
            'mailingList' => $mailingList,
        ]);
        Campaign::$plugin->sync->trigger(SyncService::EVENT_BEFORE_SYNC, $event);

        if (!$event->isValid) {
            return;
        }

        // Call for max power
        Campaign::$plugin->maxPowerLieutenant();

        // Get users in user group
        $users = User::find()
            ->groupId($mailingList->syncedUserGroupId)
            ->all();

        $total = count($users);

        foreach ($users as $i => $user) {
            // Set progress
            $this->setProgress($queue, $i / $total);

            // Sync user to contact in mailing list
            Campaign::$plugin->sync->syncUserMailingList($user, $mailingList);
        }

        // Fire an after event
        if (Campaign::$plugin->sync->hasEventHandlers(SyncService::EVENT_AFTER_SYNC)) {
            Campaign::$plugin->sync->trigger(SyncService::EVENT_AFTER_SYNC, new SyncEvent([
                'mailingList' => $mailingList,
            ]));
        }
    }

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): string
    {
        return Craft::t('campaign', 'Syncing mailing list.');
    }
}
