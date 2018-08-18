<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\models;

use craft\helpers\DateTimeHelper;
use putyourlightson\campaign\base\ScheduleModel;

/**
 * AutomatedScheduleModel
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.2.0
 */
class AutomatedScheduleModel extends ScheduleModel
{
    // Properties
    // =========================================================================

    /**
     * @var int Time delay
     */
    public $timeDelay = 0;

    /**
     * @var string Time interval
     */
    public $timeDelayInterval = '';

    /**
     * @var bool Specific time and days
     */
    public $specificTimeDays = false;

    /**
     * @var \DateTime|null Time of day
     */
    public $timeOfDay;

    /**
     * @var array|null Days of the week
     */
    public $daysOfWeek;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        $rules = parent::rules();

        $rules[] = [['timeDelay', 'timeDelayInterval'], 'required'];
        $rules[] = [['timeDelay'], 'integer', 'min' => 0];
        $rules[] = [['specificTimeDays'], 'boolean'];
        $rules[] = ['timeDelayInterval', 'in', 'range' => ['minutes', 'hours', 'days', 'weeks', 'months']];
        $rules[] = [
            ['timeOfDay', 'daysOfWeek'],
            'required',
            'when' => function($model) {
                return (bool)$model->specificTimeDays;
            }
        ];

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function isScheduledToSendNow(): bool
    {
        // If time and days were specified
        if ($this->specificTimeDays) {
            $now = new \DateTime();
            $timeOfDayToday = DateTimeHelper::toDateTime($this->timeOfDay);

            // If today is not one of "the days" or the time of day has not yet passed
            // N: Numeric representation of the day of the week: 1 to 7
            if (empty($this->daysOfWeek[$now->format('N')]) OR !DateTimeHelper::isInThePast($timeOfDayToday)) {
                return false;
            }
        }

        return true;
    }
}