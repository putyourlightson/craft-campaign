<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\jobs;

use craft\elements\User;
use putyourlightson\campaign\Campaign;

use Craft;
use craft\queue\BaseJob;
use putyourlightson\campaign\events\SyncEvent;
use putyourlightson\campaign\services\SyncService;

/**
 * SyncJob
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.2.0
 */
class SyncJob extends BaseJob
{
    // Properties
    // =========================================================================

    /**
     * @var int
     */
    public $mailingListId;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     * @throws \Exception
     * @throws \Throwable
     */
    public function execute($queue)
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

        $total = \count($users);

        foreach ($users as $i => $user) {
            // Set progress
            $this->setProgress($queue, $i / $total);

            // Sync user to mailing list
            Campaign::$plugin->sync->syncUserMailingList($user, $mailingList);
        }

        // Fire an after event
        if (Campaign::$plugin->sync->hasEventHandlers(SyncService::EVENT_AFTER_SYNC)) {
            Campaign::$plugin->sync->trigger(SyncService::EVENT_AFTER_SYNC, new SyncEvent([
                'mailingList' => $mailingList,
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
        return Craft::t('campaign', 'Syncing mailing list.');
    }
}