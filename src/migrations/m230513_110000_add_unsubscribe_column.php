<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;
use putyourlightson\campaign\records\ImportRecord;

class m230513_110000_add_unsubscribe_column extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->columnExists(ImportRecord::tableName(), 'unsubscribe')) {
            $this->addColumn(
                ImportRecord::tableName(),
                'unsubscribe',
                $this->boolean()->defaultValue(false)->notNull()->after('mailingListId'),
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
