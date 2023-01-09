<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\widgets;

use Craft;
use putyourlightson\campaign\helpers\DateRangeHelper;

/**
 * @since 2.4.0
 */
trait DateRangeWidgetTrait
{
    /**
     * @var string|null
     */
    public ?string $dateRange = null;

    /**
     * @see BaseDateRangeConditionRule::rangeTypeOptions()
     */
    public function getDateRangeOptions(): array
    {
        return [
            null => Craft::t('campaign', 'All time'),
            DateRangeHelper::TYPE_TODAY => Craft::t('app', 'Today'),
            DateRangeHelper::TYPE_THIS_WEEK => Craft::t('app', 'This week'),
            DateRangeHelper::TYPE_THIS_MONTH => Craft::t('app', 'This month'),
            DateRangeHelper::TYPE_THIS_YEAR => Craft::t('app', 'This year'),
            DateRangeHelper::TYPE_PAST_7_DAYS => Craft::t('app', 'Past {num} days', ['num' => 7]),
            DateRangeHelper::TYPE_PAST_30_DAYS => Craft::t('app', 'Past {num} days', ['num' => 30]),
            DateRangeHelper::TYPE_PAST_90_DAYS => Craft::t('app', 'Past {num} days', ['num' => 90]),
            DateRangeHelper::TYPE_PAST_YEAR => Craft::t('app', 'Past year'),
        ];
    }
}
