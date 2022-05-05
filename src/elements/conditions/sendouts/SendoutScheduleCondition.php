<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\elements\conditions\sendouts;

use craft\elements\conditions\ElementCondition;

/**
 * @since 2.0.0
 */
class SendoutScheduleCondition extends ElementCondition
{
    /**
     * @inerhitdoc
     */
    public bool $sortable = true;

    /**
     * @inheritdoc
     */
    protected function conditionRuleTypes(): array
    {
        return [];
    }
}
