<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;
use putyourlightson\campaign\records\CampaignTypeRecord;

class m220418_170000_add_campaigntype_default_status_column extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {

        if (!$this->db->columnExists(CampaignTypeRecord::tableName(), 'defaultStatus')) {
            $this->addColumn(
                CampaignTypeRecord::tableName(),
                'defaultStatus',
                $this->boolean()->defaultValue(true)->notNull()->after('handle'),
            );
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
