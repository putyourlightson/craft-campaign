<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;
use putyourlightson\campaign\records\CampaignTypeRecord;
use putyourlightson\campaign\records\ContactRecord;

class m200924_120000_add_test_contact_id_column extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $table = CampaignTypeRecord::tableName();

        if (!$this->db->columnExists($table, 'testContactId')) {
            $this->addColumn($table, 'testContactId', $this->integer()->after('queryStringParameters'));

            $this->addForeignKey(null, $table, 'testContactId', ContactRecord::tableName(), 'id', 'SET NULL');
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
