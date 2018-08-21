<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;
use craft\helpers\MigrationHelper;

/**
 * m180821_120000_add_mailinglisttype_template_columns migration.
 */
class m180821_120000_add_mailinglisttype_template_columns extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if (!$this->db->columnExists('{{%campaign_mailinglisttypes}}', 'verifyEmailTemplate')) {
            $this->addColumn('{{%campaign_mailinglisttypes}}', 'verifyEmailTemplate', $this->string()->after('doubleOptIn'));
        }
        if (!$this->db->columnExists('{{%campaign_mailinglisttypes}}', 'verifySuccessTemplate')) {
            $this->addColumn('{{%campaign_mailinglisttypes}}', 'verifySuccessTemplate', $this->string()->after('verifyEmailTemplate'));
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m180821_120000_add_mailinglisttype_template_columns cannot be reverted.\n";

        return false;
    }
}
