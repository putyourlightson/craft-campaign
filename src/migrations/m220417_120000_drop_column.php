<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;
use putyourlightson\campaign\records\CampaignTypeRecord;

class m220417_120000_drop_column extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if ($this->db->columnExists(CampaignTypeRecord::tableName(), 'enableAnonymousTracking')) {
            $this->dropColumn(CampaignTypeRecord::tableName(), 'enableAnonymousTracking');
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }
}
