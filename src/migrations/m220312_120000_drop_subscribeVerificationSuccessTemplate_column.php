<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;
use putyourlightson\campaign\records\MailingListTypeRecord;

class m220312_120000_drop_subscribeVerificationSuccessTemplate_column extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $table = MailingListTypeRecord::tableName();

        if ($this->db->columnExists($table, 'subscribeVerificationSuccessTemplate')) {
            $this->dropColumn($table, 'subscribeVerificationSuccessTemplate');
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
}
