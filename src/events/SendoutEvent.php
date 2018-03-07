<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\events;

use putyourlightson\campaign\elements\SendoutElement;

use craft\events\CancelableEvent;

/**
 * SendoutEvent
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 */
class SendoutEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    /**
     * @var SendoutElement|null
     */
    public $sendout;
}
