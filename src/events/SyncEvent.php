<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\events;

use putyourlightson\campaign\elements\MailingListElement;

use craft\events\CancelableEvent;

/**
 * SyncEvent
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.2.0
 */
class SyncEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    /**
     * @var MailingListElement|null
     */
    public $mailingList;
}
