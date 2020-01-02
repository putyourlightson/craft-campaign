<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;
use putyourlightson\campaign\records\MailingListTypeRecord;

class m190726_120000_add_rename_mailinglisttype_columns extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $table = MailingListTypeRecord::tableName();

        if ($this->db->columnExists($table, 'doubleOptIn')) {
            $this->renameColumn($table, 'doubleOptIn', 'subscribeVerificationRequired');
        }

        if ($this->db->columnExists($table, 'verifyEmailSubject')) {
            $this->renameColumn($table, 'verifyEmailSubject', 'subscribeVerificationEmailSubject');
        }

        if ($this->db->columnExists($table, 'verifyEmailTemplate')) {
            $this->renameColumn($table, 'verifyEmailTemplate', 'subscribeVerificationEmailTemplate');
        }

        if ($this->db->columnExists($table, 'verifySuccessTemplate')) {
            $this->renameColumn($table, 'verifySuccessTemplate', 'subscribeVerificationSuccessTemplate');
        }

        if (!$this->db->columnExists($table, 'unsubscribeFormAllowed')) {
            $this->addColumn($table, 'unsubscribeFormAllowed', $this->boolean()->defaultValue(false)->notNull()->after('subscribeSuccessTemplate'));
        }

        if (!$this->db->columnExists($table, 'unsubscribeVerificationEmailSubject')) {
            $this->addColumn($table, 'unsubscribeVerificationEmailSubject', $this->text()->after('unsubscribeFormAllowed'));
        }

        if (!$this->db->columnExists($table, 'unsubscribeVerificationEmailTemplate')) {
            $this->addColumn($table, 'unsubscribeVerificationEmailTemplate', $this->string(500)->after('unsubscribeVerificationEmailSubject'));
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
