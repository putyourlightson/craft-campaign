<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace modules\sitemodule\conditions\sendouts;

use craft\base\conditions\BaseConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;
use craft\elements\Entry;

class LastEntryHasImageConditionRule extends BaseConditionRule implements ElementConditionRuleInterface
{
    /**
     * @inheritdoc
     */
    public string $operator = '';

    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return 'Send only if the last entry has an image.';
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
        // Get the last entry, eager-loading the image
        $lastEntry = Entry::find()
            ->with('image')
            ->one();

        if ($lastEntry && $lastEntry->image->isNotEmpty()) {
            return true;
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(): string
    {
        return '';
    }
}
