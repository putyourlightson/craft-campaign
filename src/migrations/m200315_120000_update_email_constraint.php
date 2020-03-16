<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;
use craft\helpers\MigrationHelper;
use putyourlightson\campaign\records\ContactRecord;

class m200315_120000_update_email_constraint extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $table = ContactRecord::tableName();

        // Remove email constraint in DB for soft deleted contacts
        MigrationHelper::dropIndexIfExists($table, 'email', true, $this);
        $this->createIndex(null, $table, 'email', false);

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
