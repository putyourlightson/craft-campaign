<?php

namespace putyourlightson\campaign\migrations;

use putyourlightson\campaign\elements\SendoutElement;

use Craft;
use craft\db\Migration;
use craft\helpers\DateTimeHelper;

/**
 * m180408_120000_time_delay_intervals migration.
 */
class m180408_120000_time_delay_intervals extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $intervals = [
            DateTimeHelper::SECONDS_MINUTE => 'minutes',
            DateTimeHelper::SECONDS_HOUR => 'hours',
            DateTimeHelper::SECONDS_DAY => 'days',
            DateTimeHelper::SECONDS_DAY * 7 => 'weeks',
        ];

        $sendouts = SendoutElement::find()->sendoutType('automated')->all();

        foreach ($sendouts as $sendout) {
            $sendout->schedule['timeDelayInterval'] = $intervals[$sendout->schedule['timeDelayInterval']] ?? 'minutes';

            Craft::$app->getElements()->saveElement($sendout);
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m180408_120000_time_delay_intervals cannot be reverted.\n";

        return false;
    }
}
