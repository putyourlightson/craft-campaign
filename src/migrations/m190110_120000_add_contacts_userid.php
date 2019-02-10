<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\MailingListElement;

class m190110_120000_add_contacts_userid extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if (!$this->db->columnExists('{{%campaign_contacts}}', 'userId')) {
            $this->addColumn('{{%campaign_contacts}}', 'userId', $this->integer()->after('id'));

            $this->addForeignKey(null, '{{%campaign_contacts}}', 'userId', '{{%users}}', 'id', 'CASCADE');
        }

        // Populate contact user IDs using emails in synced mailing lists
        $mailingLists = MailingListElement::find()
            ->synced(true)
            ->all();

        foreach ($mailingLists as $mailingList) {
            Campaign::$plugin->sync->queueSync($mailingList);
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
