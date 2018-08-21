<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\base;

use craft\base\Model;

/**
 * ScheduleModel
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.2.0
 *
 * @property array $intervalOptions
 */
abstract class ScheduleModel extends Model implements ScheduleInterface
{
    // Public Methods
    // =========================================================================

    /**
     * Returns the schedule's interval options
     *
     * @return array
     */
    public function getIntervalOptions(): array
    {
        return [];
    }
}
