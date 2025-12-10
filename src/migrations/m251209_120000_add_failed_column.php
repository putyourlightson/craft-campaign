<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;
use putyourlightson\campaign\records\ContactCampaignRecord;

class m251209_120000_add_failed_column extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->columnExists(ContactCampaignRecord::tableName(), 'failed')) {
            $this->addColumn(ContactCampaignRecord::tableName(), 'failed', $this->dateTime()->after('bounced'));
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
