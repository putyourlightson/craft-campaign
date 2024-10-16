<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace modules\conditions\segments;

use craft\base\conditions\BaseConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;
use craft\elements\Entry;

class ContactIsAttendingConditionRule extends BaseConditionRule implements ElementConditionRuleInterface
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
        return 'Contact is attending.';
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
        /**
         * Gets the RSVP entry that contains an entries field with the handle `attending`, that contains the IDs of the contacts that are attending.
         */
        $rsvp = Entry::find()
            ->section('rsvp')
            ->one();

        /** @phpstan-ignore-next-line */
        $ids = $rsvp->attending->ids();

        /**
         * Filters contacts by IDs matching those that are attending.
         */
        $query->andWhere([
            'id' => $ids,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function matchElement(ElementInterface $element): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(): string
    {
        return '';
    }
}
