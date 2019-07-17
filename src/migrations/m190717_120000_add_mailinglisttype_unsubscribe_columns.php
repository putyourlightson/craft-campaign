<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;
use putyourlightson\campaign\records\MailingListTypeRecord;

class m190717_120000_add_mailinglisttype_unsubscribe_columns extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $table = MailingListTypeRecord::tableName();

        if (!$this->db->columnExists($table, 'unsubscribeEmailSubject')) {
            $this->addColumn($table, 'unsubscribeEmailSubject', $this->text()->after('subscribeSuccessTemplate'));
        }

        if (!$this->db->columnExists($table, 'unsubscribeEmailTemplate')) {
            $this->addColumn($table, 'unsubscribeEmailTemplate', $this->string(500)->after('unsubscribeEmailSubject'));
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
