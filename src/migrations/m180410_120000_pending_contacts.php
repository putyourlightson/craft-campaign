<?php

namespace putyourlightson\campaign\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;

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
        $this->createIndex(null, '{{%campaign_pendingcontacts}}', 'email, mailingListId', false);

        // Remove old pending contacts
        $pendingContacts = (new Query())
            ->select('id')
            ->from('{{%campaign_contacts}}')
            ->where(['pending' => true])
            ->all();

        foreach ($pendingContacts as $pendingContact) {
            Craft::$app->getElements()->deleteElementById($pendingContact->id);
        }

        $this->dropColumn('{{%campaign_contacts}}', 'pending');

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
