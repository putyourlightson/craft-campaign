<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;
use putyourlightson\campaign\records\ImportRecord;

class m201005_120000_add_force_subscribe_column extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $table = ImportRecord::tableName();

        if (!$this->db->columnExists($table, 'forceSubscribe')) {
            $this->addColumn($table, 'forceSubscribe', $this->boolean()->defaultValue(false)->notNull()->after('mailingListId'));
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
