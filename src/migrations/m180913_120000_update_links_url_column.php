<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;

class m180913_120000_update_links_url_column extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if ($this->db->columnExists('{{%campaign_links}}', 'url')) {
            $this->alterColumn('{{%campaign_links}}', 'url', $this->text());
        }

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
