<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;

class m181109_120000_add_mailinglisttype_subject_column extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if (!$this->db->columnExists('{{%campaign_mailinglisttypes}}', 'verifyEmailSubject')) {
            $this->addColumn('{{%campaign_mailinglisttypes}}', 'verifyEmailSubject', $this->text()->after('doubleOptIn'));
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
