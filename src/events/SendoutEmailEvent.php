<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\events;

use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\SendoutElement;

use craft\events\CancelableEvent;
use craft\mail\Message;

/**
 * SendoutEmailEvent
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 */
class SendoutEmailEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    /**
     * @var SendoutElement|null
     */
    public $sendout;

    /**
     * @var ContactElement|null
     */
    public $contact;

    /**
     * @var Message|null
     */
    public $message;

    /**
     * @var bool|null
     */
    public $success;
}
