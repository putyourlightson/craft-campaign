<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;
use putyourlightson\campaign\records\SendoutRecord;

class m260902_130000_add_segment_match_column extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->columnExists(SendoutRecord::tableName(), 'segmentMatch')) {
            $this->addColumn(SendoutRecord::tableName(), 'segmentMatch', $this->string()->defaultValue('all')->notNull()->after('segmentIds'));
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
