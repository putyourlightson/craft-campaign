<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace modules\conditions\sendouts;

use craft\base\conditions\BaseSelectConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;
use craft\elements\Entry;
use DateTime;

class RecentEntriesPublishedConditionRule extends BaseSelectConditionRule implements ElementConditionRuleInterface
{
    /**
     * @inheritdoc
     */
    public string $operator = '';

    /**
     * @inheritdoc
     */
    protected function options(): array
    {
        return [
            'week' => 'week',
            'month' => 'month',
            'year' => 'year',
        ];
    }

    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return 'Send only if an entry was published in the past';
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
        // Return whether any entries were published in the previous period
        $date = (new DateTime())->modify('-1 ' . $this->value);

        return Entry::find()
            ->after($date)
            ->exists();
    }
}
