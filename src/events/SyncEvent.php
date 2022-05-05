<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\events;

use craft\events\CancelableEvent;

use putyourlightson\campaign\elements\MailingListElement;

/**
 * @since 1.2.0
 */
class SyncEvent extends CancelableEvent
{
    /**
     * @var MailingListElement|null
     */
    public ?MailingListElement $mailingList;
}
