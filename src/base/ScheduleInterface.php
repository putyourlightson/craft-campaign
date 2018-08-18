<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\base;

/**
 * ScheduleInterface
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.2.0
*/
interface ScheduleInterface
{
    // Public Methods
    // =========================================================================

    /**
     * Returns whether the sendout is scheduled for sending now
     */
    public function isScheduledToSendNow(): bool;
}
