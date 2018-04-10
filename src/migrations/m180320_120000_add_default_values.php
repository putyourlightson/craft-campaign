<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;

/**
 * m180320_120000_add_default_values migration.
 */
class m180320_120000_add_default_values extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->alterColumn('{{%campaign_campaigns}}', 'recipients', $this->integer()->defaultValue(0)->notNull());
        $this->alterColumn('{{%campaign_campaigns}}', 'opened', $this->integer()->defaultValue(0)->notNull());
        $this->alterColumn('{{%campaign_campaigns}}', 'clicked', $this->integer()->defaultValue(0)->notNull());
        $this->alterColumn('{{%campaign_campaigns}}', 'opens', $this->integer()->defaultValue(0)->notNull());
        $this->alterColumn('{{%campaign_campaigns}}', 'clicks', $this->integer()->defaultValue(0)->notNull());
        $this->alterColumn('{{%campaign_campaigns}}', 'complained', $this->integer()->defaultValue(0)->notNull());
        $this->alterColumn('{{%campaign_campaigns}}', 'unsubscribed', $this->integer()->defaultValue(0)->notNull());
        $this->alterColumn('{{%campaign_campaigns}}', 'bounced', $this->integer()->defaultValue(0)->notNull());
        $this->alterColumn('{{%campaign_links}}', 'clicked', $this->integer()->defaultValue(0)->notNull());
        $this->alterColumn('{{%campaign_links}}', 'clicks', $this->integer()->defaultValue(0)->notNull());
        $this->alterColumn('{{%campaign_contacts_campaigns}}', 'opens', $this->integer()->defaultValue(0)->notNull());
        $this->alterColumn('{{%campaign_contacts_campaigns}}', 'clicks', $this->integer()->defaultValue(0)->notNull());
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m180320_120000_add_default_values cannot be reverted.\n";

        return false;
    }
}
