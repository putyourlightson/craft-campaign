<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace modules\conditions\segments;

use craft\base\conditions\BaseConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;
use putyourlightson\campaign\elements\ContactElement;

class ContactIsUserConditionRule extends BaseConditionRule implements ElementConditionRuleInterface
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
        return 'Contact is synced to a user.';
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
         * Filters contacts by those that have non-empty user IDs.
         */
        $query->andWhere([
            'not', [
                'userId' => null,
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function matchElement(ElementInterface $element): bool
    {
        /** @var ContactElement $element */
        return $element->userId !== null;
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(): string
    {
        return '';
    }
}
