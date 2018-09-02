<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\base;

use craft\helpers\DateTimeHelper;
use putyourlightson\campaign\elements\SendoutElement;

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
     * @var bool Can send to contacts multiple times
     */
    public $canSendToContactsMultipleTimes = false;

    /**
     * @var \DateTime|null End date
     */
    public $endDate;

    /**
     * @var array|null Days of the week
     */
    public $daysOfWeek;

    /**
     * @var \DateTime|null Time of day
     */
    public $timeOfDay;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function datetimeAttributes(): array
    {
        $attributes = parent::datetimeAttributes();
        $attributes[] = 'endDate';
        $attributes[] = 'timeOfDay';

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

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['canSendToContactsMultipleTimes'], 'boolean'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function canSendNow(SendoutElement $sendout): bool
    {
        // Ensure send date is in the past
        if (!DateTimeHelper::isInThePast($sendout->sendDate)) {
            return false;
        }

        // Ensure end date is not in the past
        if ($this->endDate !== null AND DateTimeHelper::isInThePast($this->endDate)) {
            return false;
        }

        // Ensure time of day has past
        if ($this->timeOfDay !== null) {
            $timeOfDayToday = DateTimeHelper::toDateTime($this->timeOfDay);

            if (!DateTimeHelper::isInThePast($timeOfDayToday)) {
                return false;
            }
        }

        return true;
    }
}
