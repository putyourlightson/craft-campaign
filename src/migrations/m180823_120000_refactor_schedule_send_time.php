<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;
use craft\helpers\Json;
use putyourlightson\campaign\records\SendoutRecord;

class m180823_120000_refactor_schedule_send_time extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Get automated sendouts
        $sendoutRecords = SendoutRecord::find()
            ->where(['sendoutType' => 'automated'])
            ->all();

        /** @var SendoutRecord $sendoutRecord */
        foreach ($sendoutRecords as $sendoutRecord) {
            $schedule = Json::decode($sendoutRecord->schedule);

            if (isset($schedule['specificTimeDays'])) {
                // Remove old attribute
                unset($schedule['specificTimeDays']);

                $sendoutRecord->schedule = $schedule;
                $sendoutRecord->save();
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo self::class." cannot be reverted.\n";

        return false;
    }
}
