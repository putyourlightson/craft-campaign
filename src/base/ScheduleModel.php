<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\base;

/**
 * ScheduleModel
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.2.0
 *
 * @property array $intervalOptions
 */
abstract class ScheduleModel extends BaseModel implements ScheduleInterface
{
    // Properties
    // =========================================================================

    /**
     * @var \DateTime|null End date
     */
    public $endDate;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function datetimeAttributes(): array
    {
        $attributes = parent::datetimeAttributes();
        $attributes[] = 'endDate';

        return $attributes;
    }

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
