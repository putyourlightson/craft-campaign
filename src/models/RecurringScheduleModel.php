<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\models;

use Craft;
use craft\helpers\DateTimeHelper;
use craft\validators\DateTimeValidator;
use putyourlightson\campaign\base\BaseModel;
use putyourlightson\campaign\base\ScheduleInterface;

/**
 * RecurringScheduleModel
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.2.0
 *
 * @property array $intervalOptions
 */
class RecurringScheduleModel extends BaseModel implements ScheduleInterface
{
    // Properties
    // =========================================================================

    /**
     * @var \DateTime|null End date
     */
    public $endDate;

    /**
     * @var int Frequency
     */
    public $frequency = 1;

    /**
     * @var string Frequency interval
     */
    public $frequencyInterval = '';

    /**
     * @var array|null Days of the week
     */
    public $daysOfWeek;

    /**
     * @var array|null Days of the month
     */
    public $daysOfMonth;

    /**
     * @var \DateTime Time of day
     */
    public $timeOfDay;

    // Public Methods
    // =========================================================================

    /**
     * @return array
     */
    public function getIntervalOptions(): array
    {
        return [
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

        $rules[] = [['frequency', 'timeOfDay'], 'required'];
        $rules[] = [['frequency'], 'integer', 'min' => 1];
        $rules[] = ['frequencyInterval', 'in', 'range' => array_keys($this->getIntervalOptions())];
        $rules[] = [['timeOfDay'], DateTimeValidator::class];

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function canSendNow(\DateTime $sendDate): bool
    {
        $sendTimeToday = DateTimeHelper::toDateTime($sendDate->format('H:i:s T'));

        if (!DateTimeHelper::isInThePast($sendTimeToday)) {
            return false;
        }

        // TODO: add frequency conditional
        
        if ($this->frequencyInterval == 'days') {
            return true;
        }

        $now = new \DateTime();

        // N: Numeric representation of the day of the week: 1 to 7
        if ($this->frequencyInterval == 'weeks' AND !empty($this->daysOfWeek[$now->format('N')])) {
            return true;
        }

        // j: Numeric representation of the day of the month: 1 to 31
        if ($this->frequencyInterval == 'months' AND !empty($this->daysOfMonth[$now->format('j')])) {
            return true;
        }

        return false;
    }
}