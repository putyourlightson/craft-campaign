<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\events;

use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\SendoutElement;

use craft\events\CancelableEvent;
use craft\mail\Message;

class SendoutEmailEvent extends CancelableEvent
{
    /**
     * @var SendoutElement|null
     */
    public ?SendoutElement $sendout;

    /**
     * @var ContactElement|null
     */
    public ?ContactElement $contact;

    /**
     * @var Message|null
     */
    public ?Message $message;

    /**
     * @var bool|null
     */
    public ?bool $success;
}
