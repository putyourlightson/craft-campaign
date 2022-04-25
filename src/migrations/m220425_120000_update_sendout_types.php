<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;
use putyourlightson\campaign\records\SegmentRecord;

class m220425_120000_update_sendout_types extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->columnExists(SegmentRecord::tableName(), 'contactCondition')) {
            $this->addColumn(
                SegmentRecord::tableName(),
                'contactCondition',
                $this->text()->after('segmentType'),
            );
        }

        SegmentRecord::updateAll(
            ['segmentType' => 'legacy'],
            ['segmentType' => 'regular'],
        );

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
