<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;

/**
 * m180817_120000_rename_schedule_column migration.
 */
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
        echo "m180817_120000_rename_schedule_column cannot be reverted.\n";

        return false;
    }
}
