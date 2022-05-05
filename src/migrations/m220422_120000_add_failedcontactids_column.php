<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;
use putyourlightson\campaign\records\SendoutRecord;

class m220422_120000_add_failedcontactids_column extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->columnExists(SendoutRecord::tableName(), 'failedContactIds')) {
            $this->addColumn(
                SendoutRecord::tableName(),
                'failedContactIds',
                $this->text()->after('contactIds'),
            );
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
