<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\base;

use craft\base\Model;
use craft\helpers\DateTimeHelper;
use DateTime;
use putyourlightson\campaign\elements\SendoutElement;

/**
 * @property-read array $intervalOptions
 */
abstract class ScheduleModel extends Model implements ScheduleInterface
{
    /**
     * @var bool Whether contacts can be sent to multiple times
     */
    public bool $canSendToContactsMultipleTimes = false;

    /**
     * @var DateTime|null End date
     */
    public ?DateTime $endDate = null;

    /**
     * @var array|null Days of the week
     */
    public ?array $daysOfWeek = null;

    /**
     * @var DateTime|null Time of day
     */
    public ?DateTime $timeOfDay = null;

    /**
     * Returns the schedule's interval options.
     */
    public function getIntervalOptions(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function canSendNow(SendoutElement $sendout): bool
    {
        // Ensure send date is in the past
        if (!DateTimeHelper::isInThePast($sendout->sendDate)) {
            return false;
        }

        // Ensure end date is not in the past
        if ($this->endDate !== null && DateTimeHelper::isInThePast($this->endDate)) {
            return false;
        }

        // Ensure time of day has past
        if ($this->timeOfDay !== null) {
            $now = new DateTime();
            $format = 'H:i';

            if ($this->timeOfDay->format($format) > $now->format($format)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        return [
            [['canSendToContactsMultipleTimes'], 'boolean'],
        ];
    }
}
