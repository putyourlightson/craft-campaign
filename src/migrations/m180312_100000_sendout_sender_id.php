<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\helpers\MigrationHelper;

/**
 * m180312_100000_sendout_sender_id migration.
 */
class m180312_100000_sendout_sender_id extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        MigrationHelper::renameColumn('{{%craft_sendouts}}', 'userId', 'senderId', $this);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180312_100000_sendout_sender_id cannot be reverted.\n";

        return false;
    }
}
