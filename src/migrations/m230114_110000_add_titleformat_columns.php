<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;
use putyourlightson\campaign\records\CampaignTypeRecord;

class m230114_110000_add_titleformat_columns extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->columnExists(CampaignTypeRecord::tableName(), 'hasTitleField')) {
            $this->addColumn(
                CampaignTypeRecord::tableName(),
                'hasTitleField',
                $this->boolean()->defaultValue(true)->notNull()->after('testContactIds'),
            );
        }

        if (!$this->db->columnExists(CampaignTypeRecord::tableName(), 'titleFormat')) {
            $this->addColumn(
                CampaignTypeRecord::tableName(),
                'titleFormat',
                $this->string()->after('hasTitleField'),
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
