<?php

namespace putyourlightson\campaign\migrations;

use craft\records\UserPermission;
use putyourlightson\campaign\records\ContactMailingListRecord;

use craft\db\Migration;

/**
 * m180507_120000_mlid migration.
 */
class m180507_120000_mlid extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->dropColumn('{{%campaign_mailinglists}}', 'mlid');
        $this->dropColumn('{{%campaign_mailinglisttypes}}', 'requireMlid');
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m180507_120000_mlid cannot be reverted.\n";

        return false;
    }
}
