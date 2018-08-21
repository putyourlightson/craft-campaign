<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\models;

use Craft;
use craft\helpers\DateTimeHelper;
use putyourlightson\campaign\base\ScheduleModel;
use putyourlightson\campaign\elements\SendoutElement;

/**
 * AutomatedScheduleModel
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.2.0
 *
 * @property array $intervalOptions
 */
class AutomatedScheduleModel extends ScheduleModel
{
    // Properties
    // =========================================================================

    /**
     * @var \DateTime|null End date
     */
    public $endDate;

    /**
     * @var int Time delay
     */
    public $timeDelay = 0;

    /**
     * @var string Time interval
     */
    public $timeDelayInterval = '';

    /**
     * @var array|null Days of the week
     */
    public $daysOfWeek;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getIntervalOptions(): array
    {
        return [
            'minutes' => Craft::t('campaign', 'minute(s)'),
            'hours' => Craft::t('campaign', 'hour(s)'),
            'days' => Craft::t('campaign', 'day(s)'),
            'weeks' => Craft::t('campaign', 'week(s)'),
            'months' => Craft::t('campaign', 'month(s)'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        $rules = parent::rules();

        $rules[] = [['timeDelay', 'timeDelayInterval', 'daysOfWeek'], 'required'];
        $rules[] = [['timeDelay'], 'integer', 'min' => 0];
        $rules[] = ['timeDelayInterval', 'in', 'range' => array_keys($this->getIntervalOptions())];

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function canSendNow(SendoutElement $sendout): bool
    {
        $now = new \DateTime();
        $sendTimeToday = DateTimeHelper::toDateTime($sendout->sendDate->format('H:i:s T'));

        // Ensure send time is in the past
        if (!DateTimeHelper::isInThePast($sendTimeToday)) {
            return false;
        }

        // N: Numeric representation of the day of the week: 1 to 7
        if (empty($this->daysOfWeek[$now->format('N')]) AND DateTimeHelper::isInThePast($sendTimeToday)) {
            return false;
        }

        return true;
    }
}