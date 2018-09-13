<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;

class m180329_120000_sendout_sending extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('{{%campaign_contacts_campaigns}}', 'mailingListId', $this->integer()->notNull()->after('sendoutId'));
        $this->addColumn('{{%campaign_contacts_campaigns}}', 'sent', $this->dateTime()->after('mailingListId'));
        $this->addColumn('{{%campaign_contacts_campaigns}}', 'failed', $this->dateTime()->after('sent'));
        $this->addColumn('{{%campaign_sendouts}}', 'expectedRecipients', $this->integer()->defaultValue(0)->notNull()->after('segmentIds'));
        $this->addColumn('{{%campaign_sendouts}}', 'failedRecipients', $this->integer()->defaultValue(0)->notNull()->after('expectedRecipients'));

        $this->createIndex(null, '{{%campaign_contacts_campaigns}}', 'contactId, sendoutId', true);

        $this->alterColumn('{{%campaign_sendouts}}', 'recipients', $this->integer()->defaultValue(0)->notNull());

        $this->dropColumn('{{%campaign_sendouts}}', 'pendingRecipientIds');
        $this->dropColumn('{{%campaign_sendouts}}', 'sentRecipientIds');
        $this->dropColumn('{{%campaign_sendouts}}', 'failedRecipientIds');
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
