<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;
use craft\helpers\Json;
use putyourlightson\campaign\records\SegmentRecord;

class m220322_120000_add_template_column extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->columnExists(SegmentRecord::tableName(), 'template')) {
            $this->addColumn(
                SegmentRecord::tableName(),
                'template',
                $this->text()->after('conditions'),
            );
        }

        /** @var SegmentRecord[] $records */
        $records = SegmentRecord::find()
            ->where(['segmentType' => 'template'])
            ->all();

        foreach ($records as $record) {
            $record->template = Json::decodeIfJson($record->conditions);
            $record->conditions = null;

            $record->save();
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
