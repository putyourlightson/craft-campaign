<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;

/**
 * m180503_120000_pending_contacts_source_column migration.
 */
class m180503_120000_pending_contacts_source_column extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->renameColumn('{{%campaign_pendingcontacts}}', 'sourceUrl', 'source');
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m180503_120000_pending_contacts_source_column cannot be reverted.\n";

        return false;
    }
}
