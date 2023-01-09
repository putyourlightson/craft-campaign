<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\helpers;

use craft\helpers\DateRange;
use craft\helpers\DateTimeHelper;
use DateTime;
use yii\base\InvalidArgumentException;

/**
 * TODO: remove this in 3.0 and use `DateRange` instead.
 * @see DateRange
 * @since 2.4.0
 */
class DateRangeHelper
{
    public const TYPE_TODAY = 'today';
    public const TYPE_THIS_WEEK = 'thisWeek';
    public const TYPE_THIS_MONTH = 'thisMonth';
    public const TYPE_THIS_YEAR = 'thisYear';
    public const TYPE_PAST_7_DAYS = 'past7Days';
    public const TYPE_PAST_30_DAYS = 'past30Days';
    public const TYPE_PAST_90_DAYS = 'past90Days';
    public const TYPE_PAST_YEAR = 'pastYear';

    /**
     * Returns the start and end dates for a date range by its type.
     *
     * @param string $rangeType
     * @phpstan-param self::TYPE_* $rangeType
     * @return DateTime[]
     * @phpstan-return array{DateTime,DateTime}
     */
    public static function dateRangeByType(string $rangeType): array
    {
        return match ($rangeType) {
            self::TYPE_TODAY => [
                DateTimeHelper::today(),
                DateTimeHelper::tomorrow(),
            ],
            self::TYPE_THIS_WEEK => [
                DateTimeHelper::thisWeek(),
                DateTimeHelper::nextWeek(),
            ],
            self::TYPE_THIS_MONTH => [
                DateTimeHelper::thisMonth(),
                DateTimeHelper::nextMonth(),
            ],
            self::TYPE_THIS_YEAR => [
                DateTimeHelper::thisYear(),
                DateTimeHelper::nextYear(),
            ],
            self::TYPE_PAST_7_DAYS => [
                DateTimeHelper::today()->modify('-7 days'),
                DateTimeHelper::now(),
            ],
            self::TYPE_PAST_30_DAYS => [
                DateTimeHelper::today()->modify('-30 days'),
                DateTimeHelper::now(),
            ],
            self::TYPE_PAST_90_DAYS => [
                DateTimeHelper::today()->modify('-90 days'),
                DateTimeHelper::now(),
            ],
            self::TYPE_PAST_YEAR => [
                DateTimeHelper::today()->modify('-1 year'),
                DateTimeHelper::now(),
            ],
            default => throw new InvalidArgumentException("Invalid range type: $rangeType"),
        };
    }
}
