<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\helpers\MigrationHelper;

/**
 * m180313_100000_add_verified_columns migration.
 */
class m180313_100000_add_verified_columns extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if (!$this->db->columnExists('{{%craft_contacts}}', 'verified')) {
            $this->addColumn('{{%craft_contacts}}', 'verified', $this->dateTime()->after('bounced'));
        }

        MigrationHelper::renameColumn('{{%campaign_contacts_mailinglists}}', 'confirmed', 'verified', $this);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180313_100000_add_verified_columns cannot be reverted.\n";

        return false;
    }
}
