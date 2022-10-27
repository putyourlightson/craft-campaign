<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\events;

use craft\events\CancelableEvent;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\SendoutElement;
use yii\symfonymailer\Message;

class SendoutEmailEvent extends CancelableEvent
{
    /**
     * @var SendoutElement|null
     */
    public ?SendoutElement $sendout = null;

    /**
     * @var ContactElement|null
     */
    public ?ContactElement $contact = null;

    /**
     * @var Message|null
     */
    public ?Message $message = null;

    /**
     * @var bool|null
     */
    public ?bool $success = null;
}
