<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;
use putyourlightson\campaign\records\ImportRecord;
use putyourlightson\campaign\records\SendoutRecord;

class m220427_120000_rename_columns extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->renameColumn(ImportRecord::tableName(), 'fails', 'failures');
        $this->renameColumn(SendoutRecord::tableName(), 'fails', 'failures');

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
