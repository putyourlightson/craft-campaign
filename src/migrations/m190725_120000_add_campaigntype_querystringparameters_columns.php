<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;
use putyourlightson\campaign\records\CampaignTypeRecord;
use putyourlightson\campaign\records\SendoutRecord;

class m190725_120000_add_campaigntype_querystringparameters_columns extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $table = CampaignTypeRecord::tableName();

        if (!$this->db->columnExists($table, 'queryStringParameters')) {
            $this->addColumn($table, 'queryStringParameters', $this->text()->after('plaintextTemplate'));
        }

        $table = SendoutRecord::tableName();

        if ($this->db->columnExists($table, 'subject')) {
            $this->alterColumn($table, 'subject', $this->text());
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
