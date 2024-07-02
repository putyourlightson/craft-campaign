<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\elements\conditions\contacts;

use craft\base\conditions\ConditionRuleInterface;
use craft\elements\conditions\ElementCondition;

/**
 * @since 2.0.0
 */
class ContactCondition extends ElementCondition
{
    /**
     * @inerhitdoc
     */
    public bool $sortable = true;

    /**
     * @inheritdoc
     */
    protected function isConditionRuleSelectable(ConditionRuleInterface $rule): bool
    {
        if (!parent::isConditionRuleSelectable($rule)) {
            return false;
        }

        // Allow at most one instance of `CampaignActivityConditionRule`, since modifying the query multiple times is not possible due to the `INNER JOIN`.
        if ($rule instanceof CampaignActivityConditionRule) {
            foreach ($this->getConditionRules() as $existingRule) {
                if ($existingRule instanceof CampaignActivityConditionRule) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    protected function selectableConditionRules(): array
    {
        return array_merge(parent::selectableConditionRules(), [
            EmailConditionRule::class,
            LastActivityConditionRule::class,
            CampaignActivityConditionRule::class,
        ]);
    }
}
