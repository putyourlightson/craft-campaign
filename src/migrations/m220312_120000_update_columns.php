<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;
use craft\helpers\Db;
use putyourlightson\campaign\records\CampaignTypeRecord;
use putyourlightson\campaign\records\ContactRecord;
use putyourlightson\campaign\records\MailingListTypeRecord;

class m220312_120000_update_columns extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->alterColumn(ContactRecord::tableName(), 'email', $this->string());

        if (!$this->db->columnExists(CampaignTypeRecord::tableName(), 'enableVersioning')) {
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
