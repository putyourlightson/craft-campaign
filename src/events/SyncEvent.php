<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\events;

use putyourlightson\campaign\elements\MailingListElement;

use craft\events\CancelableEvent;

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
