<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;
use putyourlightson\campaign\records\PendingContactRecord;

class m200923_120000_add_date_deleted_column extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $table = PendingContactRecord::tableName();

        if (!$this->db->columnExists($table, 'lid')) {
            $this->addColumn($table, 'dateDeleted', $this->dateTime()->null()->after('dateUpdated'));
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
