<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;
use putyourlightson\campaign\records\ImportRecord;

class m260902_120000_add_import_delimiter_column extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->columnExists(ImportRecord::tableName(), 'delimiter')) {
            $this->addColumn(ImportRecord::tableName(), 'delimiter', $this->string(9)->defaultValue('comma')->notNull()->after('filePath'));
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
