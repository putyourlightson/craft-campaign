<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\base;

use putyourlightson\campaign\elements\SendoutElement;

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
     * @param SendoutElement $sendout
     * @return bool
     */
    public function canSendNow(SendoutElement $sendout): bool;
}
