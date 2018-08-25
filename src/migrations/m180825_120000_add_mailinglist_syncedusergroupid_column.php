<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;

/**
 * m180825_120000_add_mailinglist_syncedusergroupid_column migration.
 */
class m180825_120000_add_mailinglist_syncedusergroupid_column extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if (!$this->db->columnExists('{{%campaign_mailinglists}}', 'syncedUserGroupId')) {
            $this->addColumn('{{%campaign_mailinglists}}', 'syncedUserGroupId', $this->integer()->after('mailingListTypeId'));

            $this->addForeignKey(null, '{{%campaign_mailinglists}}', 'syncedUserGroupId', '{{%usergroups}}', 'id', 'SET NULL');
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m180825_120000_add_mailinglist_syncedusergroupid_column cannot be reverted.\n";

        return false;
    }
}
