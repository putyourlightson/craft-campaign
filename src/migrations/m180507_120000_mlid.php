<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;

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
        echo self::class." cannot be reverted.\n";

        return false;
    }
}
