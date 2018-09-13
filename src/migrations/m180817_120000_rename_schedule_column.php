<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;

class m180817_120000_rename_schedule_column extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->renameColumn('{{%campaign_sendouts}}', 'automatedSchedule', 'schedule');
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
