<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\models;

use Craft;
use DateTime;
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
        if (parent::canSendNow($sendout) === false) {
            return false;
        }

        $now = new DateTime();

        // Ensure not already sent today
        $format = 'Y-m-d';
        if ($sendout->lastSent !== null AND $sendout->lastSent->format($format) == $now->format($format)) {
            return false;
        }

        $diff = $now->diff($sendout->sendDate);

        if ($this->frequencyInterval == 'days' AND ($this->frequency == 1 OR $diff->d % $this->frequency == 0)) {
            return true;
        }

        // N: Numeric representation of the day of the week: 1 to 7
        if ($this->frequencyInterval == 'weeks' AND !empty($this->daysOfWeek[$now->format('N')]) AND ($this->frequency == 1 OR floor($diff->d / 7) % $this->frequency == 0)) {
            return true;
        }

        // j: Numeric representation of the day of the month: 1 to 31
        if ($this->frequencyInterval == 'months' AND !empty($this->daysOfMonth[$now->format('j')]) AND ($this->frequency == 1 OR $diff->m % $this->frequency == 0)) {
            return true;
        }

        return false;
    }
}
