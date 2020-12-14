<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;
use putyourlightson\campaign\records\ImportRecord;

class m201214_120000_add_asset_id_column extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $table = ImportRecord::tableName();

        if (!$this->db->columnExists($table, 'assetId')) {
            $this->addColumn($table, 'assetId', $this->integer()->after('id'));
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
