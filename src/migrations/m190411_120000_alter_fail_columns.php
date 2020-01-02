<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;

class m190411_120000_alter_fail_columns extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Rename columns
        if ($this->db->columnExists('{{%campaign_sendouts}}', 'failedRecipients')) {
            $this->renameColumn('{{%campaign_sendouts}}', 'failedRecipients', 'fails');
        }

        if ($this->db->columnExists('{{%campaign_imports}}', 'failed')) {
            $this->renameColumn('{{%campaign_imports}}', 'failed', 'fails');
        }

        // Drop columns
        if ($this->db->columnExists('{{%campaign_contacts_campaigns}}', 'failed')) {
            $this->dropColumn('{{%campaign_contacts_campaigns}}', 'failed');
        }

        if ($this->db->columnExists('{{%campaign_sendouts}}', 'sendStatusMessage')) {
            $this->dropColumn('{{%campaign_sendouts}}', 'sendStatusMessage');
        }

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
