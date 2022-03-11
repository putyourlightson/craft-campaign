<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\events;

use craft\events\CancelableEvent;

use putyourlightson\campaign\elements\SendoutElement;

class SendoutEvent extends CancelableEvent
{
    /**
     * @var SendoutElement|null
     */
    public ?SendoutElement $sendout;
}
