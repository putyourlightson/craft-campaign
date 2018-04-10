<?php

namespace putyourlightson\campaign\migrations;

use craft\helpers\MigrationHelper;

use craft\db\Migration;

/**
 * m180410_120000_pending_contacts migration.
 */
class m180410_120000_pending_contacts extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createTable('{{%campaign_pendingcontacts}}', [
            'id' => $this->primaryKey(),
            'pid' => $this->uid(),
            'email' => $this->string()->notNull(),
            'mailingListId' => $this->integer()->notNull(),
            'sourceUrl' => $this->string(),
            'fieldData' => $this->text(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createIndex(null, '{{%campaign_pendingcontacts}}', 'pid', true);
        $this->createIndex(null, '{{%campaign_pendingcontacts}}', 'email, mailingListId', true);
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m180410_120000_pending_contacts cannot be reverted.\n";

        return false;
    }
}
