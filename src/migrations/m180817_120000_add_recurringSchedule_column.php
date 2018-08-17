<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;
use craft\helpers\MigrationHelper;

/**
 * m180817_120000_add_recurringSchedule_column migration.
 */
class m180817_120000_add_recurringSchedule_column extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if (!$this->db->columnExists('{{%campaign_sendouts}}', 'recurringSchedule')) {
            $this->addColumn('{{%campaign_sendouts}}', 'recurringSchedule', $this->text()->after('automatedSchedule'));
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m180817_120000_add_recurringSchedule_column cannot be reverted.\n";

        return false;
    }
}
