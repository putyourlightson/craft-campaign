<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\events;

use putyourlightson\campaign\elements\SendoutElement;

use craft\events\CancelableEvent;

class SendoutEvent extends CancelableEvent
{
    /**
     * @var SendoutElement|null
     */
    public ?SendoutElement $sendout;
}
