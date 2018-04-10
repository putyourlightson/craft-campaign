<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;

/**
 * m180405_120000_remove_expected_recipients migration.
 */
class m180405_120000_remove_expected_recipients extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->dropColumn('{{%campaign_sendouts}}', 'expectedRecipients');
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m180405_120000_remove_expected_recipients cannot be reverted.\n";

        return false;
    }
}
