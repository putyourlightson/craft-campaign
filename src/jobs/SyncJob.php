<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\jobs;

use Craft;
use craft\base\Batchable;
use craft\db\QueryBatcher;
use craft\elements\User;
use craft\queue\BaseBatchedJob;
use putyourlightson\campaign\batchers\RowBatcher;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\events\SyncEvent;
use putyourlightson\campaign\services\SyncService;

/**
 * @since 1.2.0
 */
class SyncJob extends BaseBatchedJob
{
    /**
     * @var int
     */
    public int $mailingListId;

    /**
     * @var MailingListElement|null
     */
    public ?MailingListElement $_mailingList = null;

    /**
     * @inheritdoc
     */
    public function execute($queue): void
    {
        $mailingList = $this->_getMailingList();
        if ($mailingList === null || $mailingList->syncedUserGroupId === null) {
            return;
        }

        $event = new SyncEvent([
            'mailingList' => $mailingList,
        ]);
        Campaign::$plugin->sync->trigger(SyncService::EVENT_BEFORE_SYNC, $event);

        if (!$event->isValid) {
            return;
        }

        parent::execute($queue);

        // TODO: move what’s below this into the `BaseBatchedJob::after` method in Campaign 3.

        if ($this->itemOffset < $this->totalItems()) {
            return;
        }

        if (Campaign::$plugin->sync->hasEventHandlers(SyncService::EVENT_AFTER_SYNC)) {
            Campaign::$plugin->sync->trigger(SyncService::EVENT_AFTER_SYNC, $event);
        }
    }

    /**
     * @inheritdoc
     */
    protected function loadData(): Batchable
    {
        $mailingList = $this->_getMailingList();
        if ($mailingList === null || $mailingList->syncedUserGroupId === null) {
            return new RowBatcher([]);
        }

        $query = User::find()
            ->groupId($mailingList->syncedUserGroupId);

        return new QueryBatcher($query);
    }

    /**
     * @inheritdoc
     */
    protected function processItem(mixed $item): void
    {
        Campaign::$plugin->sync->syncUserMailingList($item, $this->_getMailingList());
    }

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): string
    {
        return Craft::t('campaign', 'Syncing mailing list.');
    }

    private function _getMailingList(): ?MailingListElement
    {
        if ($this->_mailingList === null) {
            $this->_mailingList = Campaign::$plugin->mailingLists->getMailingListById($this->mailingListId);
        }

        return $this->_mailingList;
    }
}
