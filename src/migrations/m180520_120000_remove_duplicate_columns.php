<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;

class m180520_120000_remove_duplicate_columns extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->dropColumn('{{%campaign_contacts_campaigns}}', 'country');
        $this->dropColumn('{{%campaign_contacts_campaigns}}', 'geoIp');
        $this->dropColumn('{{%campaign_contacts_campaigns}}', 'device');
        $this->dropColumn('{{%campaign_contacts_campaigns}}', 'os');
        $this->dropColumn('{{%campaign_contacts_campaigns}}', 'client');

        $this->dropColumn('{{%campaign_contacts_mailinglists}}', 'country');
        $this->dropColumn('{{%campaign_contacts_mailinglists}}', 'geoIp');
        $this->dropColumn('{{%campaign_contacts_mailinglists}}', 'device');
        $this->dropColumn('{{%campaign_contacts_mailinglists}}', 'os');
        $this->dropColumn('{{%campaign_contacts_mailinglists}}', 'client');

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
