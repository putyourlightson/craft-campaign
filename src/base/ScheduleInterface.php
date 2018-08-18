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
     * Returns whether the sendout can be sent now
     *
     * @param \DateTime $sendDate
     * @return bool
     */
    public function canSendNow(\DateTime $sendDate): bool;
}
