<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;
use putyourlightson\campaign\helpers\StringHelper;
use putyourlightson\campaign\records\SendoutRecord;

class m220322_120000_convert_columns_to_json extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $records = SendoutRecord::find()->all();

        foreach ($records as $record) {
            $record->contactIds = $this->_asArrayOrNull($record->contactIds);
            $record->mailingListIds = $this->_asArrayOrNull($record->mailingListIds);
            $record->excludedMailingListIds = $this->_asArrayOrNull($record->excludedMailingListIds);
            $record->segmentIds = $this->_asArrayOrNull($record->segmentIds);

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

    /**
     * Returns values as an array or null.
     */
    private function _asArrayOrNull($value): ?array
    {
        if ($value && is_string($value)) {
            return StringHelper::split($value);
        }

        return null;
    }
}
