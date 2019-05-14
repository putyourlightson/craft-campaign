<?php

namespace putyourlightson\campaign\migrations;

use Craft;
use craft\db\Migration;
use putyourlightson\campaign\records\SegmentRecord;

class m190506_120000_add_assign_segment_types extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $table = SegmentRecord::tableName();

        if (!$this->db->columnExists($table, 'segmentType')) {
            $this->addColumn($table, 'segmentType', $this->string()->notNull()->after('id'));

            $this->createIndex(null, $table, 'segmentType', false);
        }

        // Assign segment types to all segments
        $segmentRecords = SegmentRecord::find()->all();

        foreach ($segmentRecords as $segmentRecord) {
            $segmentRecord->segmentType = 'regular';
            $segmentRecord->save();
        }

        // Refresh the db schema caches
        Craft::$app->db->schema->refresh();
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
