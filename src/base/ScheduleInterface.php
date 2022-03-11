<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\base;

use putyourlightson\campaign\elements\SendoutElement;

/**
 * @since 1.2.0
*/
interface ScheduleInterface
{
    /**
     * Returns whether the sendout can be sent now.
     */
    public function canSendNow(SendoutElement $sendout): bool;
}
