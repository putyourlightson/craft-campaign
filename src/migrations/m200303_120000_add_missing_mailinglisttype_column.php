<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;
use putyourlightson\campaign\records\MailingListTypeRecord;

class m200303_120000_add_missing_mailinglisttype_column extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $table = MailingListTypeRecord::tableName();

        if (!$this->db->columnExists($table, 'subscribeSuccessTemplate')) {
            $this->addColumn($table, 'subscribeSuccessTemplate', $this->string(500)->after('subscribeVerificationSuccessTemplate'));
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
