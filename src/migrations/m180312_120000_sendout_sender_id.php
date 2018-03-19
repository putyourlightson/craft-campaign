<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;
use craft\helpers\MigrationHelper;

/**
 * m180312_120000_sendout_sender_id migration.
 */
class m180312_120000_sendout_sender_id extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if ($this->db->columnExists('{{%campaign_sendouts}}', 'userId')) {
            MigrationHelper::renameColumn('{{%campaign_sendouts}}', 'userId', 'senderId', $this);
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180312_120000_sendout_sender_id cannot be reverted.\n";

        return false;
    }
}
