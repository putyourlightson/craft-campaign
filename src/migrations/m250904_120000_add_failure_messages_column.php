<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;
use putyourlightson\campaign\records\ImportRecord;

class m250904_120000_add_failure_messages_column extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->columnExists(ImportRecord::tableName(), 'failedRows')) {
            $this->addColumn(ImportRecord::tableName(), 'failedRows', $this->text()->after('failures'));
        }

        if (!$this->db->columnExists(ImportRecord::tableName(), 'failureMessages')) {
            $this->addColumn(ImportRecord::tableName(), 'failureMessages', $this->text()->after('failedRows'));
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }
}
