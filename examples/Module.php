<?php

namespace modules;

use Craft;
use craft\base\conditions\BaseCondition;
use craft\events\RegisterConditionRulesEvent;
use modules\conditions\segments\ContactIsUserConditionRule;
use modules\conditions\sendouts\LastEntryHasImageConditionRule;
use modules\conditions\sendouts\MondayMorningSendoutConditionRule;
use modules\conditions\sendouts\RecentEntriesPublishedConditionRule;
use putyourlightson\campaign\elements\conditions\contacts\ContactCondition;
use putyourlightson\campaign\elements\conditions\sendouts\SendoutScheduleCondition;
use yii\base\Event;

class Module extends \yii\base\Module
{
    public function init(): void
    {
        Craft::setAlias('@modules', __DIR__);

        parent::init();

        Event::on(
            ContactCondition::class,
            BaseCondition::EVENT_REGISTER_CONDITION_RULES,
            function(RegisterConditionRulesEvent $event) {
                $event->conditionRules[] = ContactIsUserConditionRule::class;
            }
        );

        Event::on(
            SendoutScheduleCondition::class,
            BaseCondition::EVENT_REGISTER_CONDITION_RULES,
            function(RegisterConditionRulesEvent $event) {
                $event->conditionRules[] = LastEntryHasImageConditionRule::class;
                $event->conditionRules[] = MondayMorningSendoutConditionRule::class;
                $event->conditionRules[] = RecentEntriesPublishedConditionRule::class;
            }
        );
    }
}
