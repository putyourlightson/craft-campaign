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
class AutomatedScheduleModel extends ScheduleModel
{
    /**
     * @var int Time delay
     */
    public int $timeDelay = 0;

    /**
     * @var string Time interval
     */
    public string $timeDelayInterval = '';

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
    protected function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            [['timeDelay', 'timeDelayInterval', 'daysOfWeek'], 'required'],
            [['timeDelay'], 'integer', 'min' => 0],
            ['timeDelayInterval', 'in', 'range' => array_keys($this->getIntervalOptions())],
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

        // Ensure day of the week is valid.
        // `N` is the numeric representation of the day of the week: 1 to 7
        if (!empty($this->daysOfWeek[$now->format('N')])) {
            return true;
        }

        return false;
    }
}
