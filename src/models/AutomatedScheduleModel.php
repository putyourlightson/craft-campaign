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
     * @var int Time interval
     */
    public $timeDelayInterval = 60;

    /**
     * @var bool Specific time and days
     */
    public $specificTimeDays = false;

    /**
     * @var \DateTime|null Time of day
     */
    public $timeOfDay;

    /**
     * @var array|null Day of the week
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
            [['timeDelay'], 'number'],
            [['specificTimeDays'], 'boolean'],
            [['timeDelay', 'timeDelayInterval'], 'required'],
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