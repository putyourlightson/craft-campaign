<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;

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
        echo self::class." cannot be reverted.\n";

        return false;
    }
}
