<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;
use putyourlightson\campaign\records\BatchEmailRecord;

class m240603_120000_add_batchemails_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->tableExists(BatchEmailRecord::tableName())) {
            $this->createTable(BatchEmailRecord::tableName(), [
                'id' => $this->integer()->notNull(),
                'sid' => (new Install())->shortUid(),
                'fromName' => $this->string(),
                'fromEmail' => $this->string(),
                'replyToEmail' => $this->string(),
                'to' => $this->string(),
                'subject' => $this->text(),
                'htmlBody' => $this->mediumText(),
                'plaintextBody' => $this->mediumText(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
                'PRIMARY KEY([[id]])',
            ]);
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
