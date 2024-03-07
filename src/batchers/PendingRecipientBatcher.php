<?php

namespace putyourlightson\campaign\batchers;

use craft\base\Batchable;

/**
 * @since 2.13.0
 */
class PendingRecipientBatcher implements Batchable
{
    public function __construct(
        private array $pendingRecipients,
        private ?int $limit = null,
    ) {
    }

    /**
     * @inheritdoc
     */
    public function count(): int
    {
        return min(count($this->pendingRecipients), $this->limit ?? 0);
    }

    /**
     * Returns a batch of pending recipients, using the limit and ignoring the offset, since the pending recipients array is calculated fresh each time.
     */
    public function getSlice(int $offset, int $limit): iterable
    {
        return array_slice($this->pendingRecipients, 0, $limit);
    }
}
