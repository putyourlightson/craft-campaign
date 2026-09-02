<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\models;

use Craft;
use DateTime;
use putyourlightson\campaign\base\ScheduleModel;
use putyourlightson\campaign\elements\SendoutElement;

/**
 * @since 1.2.0
 */
class RecurringScheduleModel extends ScheduleModel
{
    /**
     * @var int Frequency
     */
    public int $frequency = 1;

    /**
     * @var string Frequency interval
     */
    public string $frequencyInterval = '';

    /**
     * @var string Monthly schedule type
     */
    public string $monthlyScheduleType = 'daysOfMonth';

    /**
     * @var array Days of the month
     */
    public array $daysOfMonth = [];

    /**
     * @var array Days of the week in a month
     */
    public array $daysOfWeekInMonth = [];

    /**
     * @var array Weeks of the month
     */
    public array $weeksOfMonth = [];

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
     * Returns the available monthly schedule types.
     */
    public function getMonthlyScheduleTypeOptions(): array
    {
        return [
            'daysOfMonth' => Craft::t('campaign', 'Specific days of the month'),
            'daysOfWeek' => Craft::t('campaign', 'Days of the week'),
        ];
    }

    /**
     * Returns the available weeks of the month.
     */
    public function getWeekOfMonthOptions(): array
    {
        return [
            'first' => Craft::t('campaign', 'First'),
            'second' => Craft::t('campaign', 'Second'),
            'third' => Craft::t('campaign', 'Third'),
            'fourth' => Craft::t('campaign', 'Fourth'),
            'fifth' => Craft::t('campaign', 'Fifth'),
            'last' => Craft::t('campaign', 'Last'),
        ];
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            [['frequency'], 'required'],
            [['frequency'], 'integer', 'min' => 1],
            ['frequencyInterval', 'in', 'range' => array_keys($this->getIntervalOptions())],
            ['monthlyScheduleType', 'in', 'range' => array_keys($this->getMonthlyScheduleTypeOptions())],
            ['daysOfWeekInMonth', 'required', 'when' => fn() => $this->frequencyInterval === 'months' && $this->monthlyScheduleType === 'daysOfWeek'],
            ['weeksOfMonth', 'required', 'when' => fn() => $this->frequencyInterval === 'months' && $this->monthlyScheduleType === 'daysOfWeek'],
            ['weeksOfMonth', 'each', 'rule' => ['in', 'range' => array_keys($this->getWeekOfMonthOptions())]],
            [['daysOfMonth', 'daysOfWeekInMonth', 'weeksOfMonth'], 'safe'],
        ]);
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
        if ($sendout->lastSent !== null) {
            if ($sendout->lastSent->format('Y-m-d') == $now->format('Y-m-d')) {
                return false;
            }
        }

        return $this->matchesDate($sendout, $now);
    }

    /**
     * Returns whether the schedule matches the provided date.
     */
    protected function matchesDate(SendoutElement $sendout, DateTime $date): bool
    {
        $diff = $date->diff($sendout->sendDate);

        if ($this->frequencyInterval == 'days' && ($this->frequency == 1 || $diff->days % $this->frequency == 0)) {
            return true;
        }

        // N: Numeric representation of the day of the week (1 to 7)
        if ($this->frequencyInterval == 'weeks'
            && !empty($this->daysOfWeek[$date->format('N')])
            && ($this->frequency == 1 || floor($diff->days / 7) % $this->frequency == 0)
        ) {
            return true;
        }

        if ($this->frequencyInterval == 'months') {
            $months = ((int)$date->format('Y') - (int)$sendout->sendDate->format('Y')) * 12
                + (int)$date->format('n') - (int)$sendout->sendDate->format('n');

            if ($this->frequency > 1 && $months % $this->frequency !== 0) {
                return false;
            }

            if ($this->monthlyScheduleType === 'daysOfWeek') {
                return $this->matchesWeekOfMonth($date);
            }

            // j: Numeric representation of the day of the month (1 to 31)
            return !empty($this->daysOfMonth[$date->format('j')]);
        }

        return false;
    }

    /**
     * Returns whether the provided date matches the selected week of the month.
     */
    private function matchesWeekOfMonth(DateTime $date): bool
    {
        if (empty($this->daysOfWeekInMonth[$date->format('N')])) {
            return false;
        }

        $dayOfMonth = (int)$date->format('j');

        if (in_array('last', $this->weeksOfMonth, true)
            && $dayOfMonth + 7 > (int)$date->format('t')
        ) {
            return true;
        }

        $weekOfMonth = intdiv($dayOfMonth - 1, 7) + 1;
        $week = match ($weekOfMonth) {
            1 => 'first',
            2 => 'second',
            3 => 'third',
            4 => 'fourth',
            5 => 'fifth',
            default => '',
        };

        return in_array($week, $this->weeksOfMonth, true);
    }
}
