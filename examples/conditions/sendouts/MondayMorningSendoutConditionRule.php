<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace modules\conditions\sendouts;

use craft\base\conditions\BaseConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;
use DateTime;

class MondayMorningSendoutConditionRule extends BaseConditionRule implements ElementConditionRuleInterface
{
    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return 'Send only between 10:00 and 11:00 on Mondays';
    }

    /**
     * @inheritdoc
     */
    public function getExclusiveQueryParams(): array
    {
        return [self::class];
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(ElementQueryInterface $query): void
    {
    }

    /**
     * @inheritdoc
     */
    public function matchElement(ElementInterface $element): bool
    {
        $now = new DateTime();

        // Return whether it is now between 10:00 and 11:00 on Monday.
        return $now->format('H') === '10' && $now->format('N') === '1';
    }
}
