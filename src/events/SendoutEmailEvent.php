<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\events;

use craft\events\CancelableEvent;
use yii\symfonymailer\Message;

use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\SendoutElement;

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
