<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;
use putyourlightson\campaign\records\ContactRecord;

class m210102_120000_add_blocked_column extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $table = ContactRecord::tableName();

        if (!$this->db->columnExists($table, 'blocked')) {
            $this->addColumn($table, 'blocked', $this->dateTime()->after('bounced'));
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
