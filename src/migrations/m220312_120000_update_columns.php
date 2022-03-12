<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;
use putyourlightson\campaign\records\CampaignTypeRecord;
use putyourlightson\campaign\records\MailingListTypeRecord;

class m220312_120000_update_columns extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if (!$this->db->columnExists(CampaignTypeRecord::tableName(), 'subscribeVerificationSuccessTemplate')) {
            $this->addColumn(
                CampaignTypeRecord::tableName(),
                'enableVersioning',
                $this->boolean()->defaultValue(true)->notNull()->after('handle'),
            );
        }

        if ($this->db->columnExists(MailingListTypeRecord::tableName(), 'subscribeVerificationSuccessTemplate')) {
            $this->dropColumn(
                MailingListTypeRecord::tableName(),
                'subscribeVerificationSuccessTemplate',
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
