<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;
use putyourlightson\campaign\records\ImportRecord;

class m190422_120000_remove_failures_column extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if ($this->db->columnExists('{{%campaign_imports}}', 'failures')) {
            $this->dropColumn('{{%campaign_imports}}', 'failures');
        }
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
