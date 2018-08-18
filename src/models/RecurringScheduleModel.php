<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\models;

use craft\helpers\DateTimeHelper;
use craft\validators\DateTimeValidator;
use putyourlightson\campaign\base\ScheduleModel;

/**
 * RecurringScheduleModel
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.2.0
 */
class RecurringScheduleModel extends ScheduleModel
{
    // Properties
    // =========================================================================

    /**
     * @var string Frequency
     */
    public $frequency = '';

    /**
     * @var int Interval
     */
    public $interval = 1;

    /**
     * @var int Max occurrences
     */
    public $maxOccurrences = 0;

    /**
     * @var \DateTime Time of day
     */
    public $timeOfDay;

    /**
     * @var array|null Days of the week
     */
    public $daysOfWeek;

    /**
     * @var array|null Days of the month
     */
    public $daysOfMonth;

    /**
     * @var array|null Months of the year
     */
    public $monthsOfYear;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        $rules = parent::rules();

        $rules[] = [['frequency', 'timeOfDay'], 'required'];
        $rules[] = [['frequency', 'maxOccurrences'], 'integer'];
        $rules[] = [['frequency'], 'min' => 1];
        $rules[] = [['timeOfDay'], DateTimeValidator::class];

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function isScheduledToSendNow(): bool
    {
        $timeOfDayToday = DateTimeHelper::toDateTime($this->timeOfDay);

        if (!DateTimeHelper::isInThePast($timeOfDayToday)) {
            return false;
        }

        if ($this->frequency == 'daily') {
            return true;
        }

        $now = new \DateTime();

        // N: Numeric representation of the day of the week: 1 to 7
        if ($this->frequency == 'weekly' AND !empty($this->daysOfWeek[$now->format('N')])) {
            return true;
        }

        // j: Numeric representation of the day of the month: 1 to 31
        if ($this->frequency == 'monthly' AND !empty($this->daysOfMonth[$now->format('j')])) {
            return true;
        }

        // n: Numeric representation of the month: 1 to 12
        if ($this->frequency == 'yearly' AND !empty($this->monthsOfYear[$now->format('n')])) {
            return true;
        }

        return false;
    }
}