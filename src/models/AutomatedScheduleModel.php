<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\models;

use putyourlightson\campaign\base\BaseModel;

/**
 * AutomatedScheduleModel
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0   
 */
class AutomatedScheduleModel extends BaseModel
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
        return [
            [['timeDelay'], 'integer', 'min' => 0],
            [['specificTimeDays'], 'boolean'],
            [['timeDelay', 'timeDelayInterval'], 'required'],
            ['timeDelayInterval', 'in', 'range' => ['minutes', 'hours', 'days', 'weeks', 'months']],
            [
                ['timeOfDay', 'daysOfWeek'],
                'required',
                'when' => function($model) {
                    return (bool)$model->specificTimeDays;
                }
            ],
        ];
    }
}