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
    public ?MailingListElement $mailingList = null;

    /**
     * @inheritdoc
     */
    public function execute($queue): void
    {
        $mailingList = $this->getMailingList();
        if ($mailingList === null || $mailingList->syncedUserGroupId === null) {
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

        parent::execute($queue);
    }

    /**
     * @inheritdoc
     */
    protected function after(): void
    {
        // Fire an after event
        if (Campaign::$plugin->sync->hasEventHandlers(SyncService::EVENT_AFTER_SYNC)) {
            Campaign::$plugin->sync->trigger(SyncService::EVENT_AFTER_SYNC, new SyncEvent([
                'mailingList' => $this->getMailingList(),
            ]));
        }
    }

    /**
     * @inheritdoc
     */
    protected function loadData(): Batchable
    {
        $mailingList = $this->getMailingList();
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
        Campaign::$plugin->sync->syncUserMailingList($item, $this->getMailingList());
    }

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): string
    {
        return Craft::t('campaign', 'Syncing mailing list.');
    }

    private function getMailingList(): ?MailingListElement
    {
        if ($this->mailingList === null) {
            $this->mailingList = Campaign::$plugin->mailingLists->getMailingListById($this->mailingListId);
        }

        return $this->mailingList;
    }
}
