<?php

namespace putyourlightson\campaign\batchers;

use craft\base\Batchable;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\SendoutElement;

/**
 * @since 2.13.0
 */
class SendoutBatcher implements Batchable
{
    public function __construct(
        private ?SendoutElement $sendout,
    ) {
    }

    /**
     * @inheritdoc
     */
    public function count(): int
    {
        if ($this->sendout === null) {
            return 0;
        }

        // Return the number of pending plus sent recipients.
        $pendingRecipientCount = Campaign::$plugin->sendouts->getPendingRecipientCount($this->sendout);

        return $pendingRecipientCount + $this->sendout->recipients;
    }

    /**
     * Returns a batch of pending recipients, using the limit and ignoring the offset, since the pending recipients array is calculated fresh each time.
     */
    public function getSlice(int $offset, int $limit): iterable
    {
        return Campaign::$plugin->sendouts->getPendingRecipients($this->sendout, $limit);
    }
}
