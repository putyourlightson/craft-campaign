<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;

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
