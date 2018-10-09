<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;

class m180830_120000_update_sendouts_htmlbody_column extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if ($this->db->columnExists('{{%campaign_sendouts}}', 'htmlBody')) {
            $this->alterColumn('{{%campaign_sendouts}}', 'htmlBody', $this->mediumText());
        }

        if ($this->db->columnExists('{{%campaign_sendouts}}', 'plaintextBody')) {
            $this->alterColumn('{{%campaign_sendouts}}', 'plaintextBody', $this->mediumText());
        }
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
