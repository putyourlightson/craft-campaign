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
     * @var array Days of the month
     */
    public array $daysOfMonth = [];

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
    protected function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            [['frequency'], 'required'],
            [['frequency'], 'integer', 'min' => 1],
            ['frequencyInterval', 'in', 'range' => array_keys($this->getIntervalOptions())],
            [['daysOfMonth'], 'safe'],
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
        $format = 'Y-m-d';
        if ($sendout->lastSent !== null && $sendout->lastSent->format($format) == $now->format($format)) {
            return false;
        }

        $diff = $now->diff($sendout->sendDate);

        if ($this->frequencyInterval == 'days' && ($this->frequency == 1 || $diff->d % $this->frequency == 0)) {
            return true;
        }

        // N: Numeric representation of the day of the week (1 to 7)
        if ($this->frequencyInterval == 'weeks'
            && !empty($this->daysOfWeek[$now->format('N')])
            && ($this->frequency == 1 || floor($diff->d / 7) % $this->frequency == 0)
        ) {
            return true;
        }

        // j: Numeric representation of the day of the month (1 to 31)
        if ($this->frequencyInterval == 'months'
            && !empty($this->daysOfMonth[$now->format('j')])
            && ($this->frequency == 1 || $diff->m % $this->frequency == 0)
        ) {
            return true;
        }

        return false;
    }
}
