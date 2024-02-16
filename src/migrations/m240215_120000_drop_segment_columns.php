<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;
use putyourlightson\campaign\records\SegmentRecord;

class m240215_120000_drop_segment_columns extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if ($this->db->columnExists(SegmentRecord::tableName(), 'segmentType')) {
            $this->dropColumn(SegmentRecord::tableName(), 'segmentType');
        }
        if ($this->db->columnExists(SegmentRecord::tableName(), 'conditions')) {
            $this->dropColumn(SegmentRecord::tableName(), 'conditions');
        }
        if ($this->db->columnExists(SegmentRecord::tableName(), 'template')) {
            $this->dropColumn(SegmentRecord::tableName(), 'template');
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
