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
 * RecurringScheduleModel
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.2.0
 *
 * @property array $intervalOptions
 */
class RecurringScheduleModel extends ScheduleModel
{
    // Properties
    // =========================================================================

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

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
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

        $rules[] = [['frequency'], 'required'];
        $rules[] = [['frequency'], 'integer', 'min' => 1];
        $rules[] = ['frequencyInterval', 'in', 'range' => array_keys($this->getIntervalOptions())];

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function canSendNow(SendoutElement $sendout): bool
    {
        if ($sendout->lastSent === null) {
            return true;
        }

        $now = new \DateTime();

        // Ensure not already sent today
        if ($now->diff($sendout->lastSent)->d == 0) {
            return false;
        }

        $sendTimeToday = new \DateTime($sendout->sendDate->format('H:i:s T'));

        // Ensure send time is in the past
        if (!DateTimeHelper::isInThePast($sendTimeToday)) {
            return false;
        }

        $diff = $now->diff($sendout->sendDate);

        if ($this->frequencyInterval == 'days') {
            return ($this->frequency == 1 OR $diff->d % $this->frequency == 0);
        }

        if ($this->frequencyInterval == 'weeks') {
            // N: Numeric representation of the day of the week: 1 to 7
            if (empty($this->daysOfWeek[$now->format('N')])) {
                return false;
            }

            if ($this->frequency > 1 AND floor($diff->d / 7) % $this->frequency != 0) {
                return false;
            }

            return true;
        }

        if ($this->frequencyInterval == 'months') {
            // j: Numeric representation of the day of the month: 1 to 31
            if (empty($this->daysOfMonth[$now->format('j')])) {
                return false;
            }

            if ($this->frequency > 1 AND $diff->m % $this->frequency != 0) {
                return false;
            }

            return true;
        }

        return false;
    }
}