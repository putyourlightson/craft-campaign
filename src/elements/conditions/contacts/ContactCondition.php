<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\elements\conditions\contacts;

use craft\elements\conditions\ElementCondition;

/**
 * @since 2.0.0
 */
class ContactCondition extends ElementCondition
{
    /**
     * @inheritdoc
     */
    protected function conditionRuleTypes(): array
    {
        return array_merge(parent::conditionRuleTypes(), [
            EmailConditionRule::class,
        ]);
    }
}
