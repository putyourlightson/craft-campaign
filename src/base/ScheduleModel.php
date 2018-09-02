<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\base;

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
     * @var \DateTime|null|bool End date
     */
    public $endDate;

    /**
     * @var array|null Days of the week
     */
    public $daysOfWeek;

    /**
     * @var \DateTime|null|bool Time of day
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
        $now = new \DateTime();
        $timeOfDayToday = DateTimeHelper::toDateTime($this->timeOfDay);

        // Ensure send date is in the past and time of day is not set or is has past
        if (DateTimeHelper::isInThePast($sendout->sendDate) AND (empty($this->timeOfDay) OR DateTimeHelper::isInThePast($timeOfDayToday))) {
            return true;
        }

        return false;
    }
}
