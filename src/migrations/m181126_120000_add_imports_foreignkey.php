<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;

class m181126_120000_add_imports_foreignkey extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addForeignKey(null, '{{%campaign_imports}}', 'mailingListId', '{{%campaign_mailinglists}}', 'id', 'SET NULL');
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
