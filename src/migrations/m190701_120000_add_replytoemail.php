<?php

namespace putyourlightson\campaign\migrations;

use Craft;
use craft\db\Migration;
use putyourlightson\campaign\records\SendoutRecord;

class m190701_120000_add_replytoemail extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $table = SendoutRecord::tableName();

        if (!$this->db->columnExists($table, 'replyToEmail')) {
            $this->addColumn($table, 'replyToEmail', $this->string()->after('fromEmail'));
        }

        // Refresh the db schema caches
        Craft::$app->db->schema->refresh();

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
