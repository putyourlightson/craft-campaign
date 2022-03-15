<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;
use putyourlightson\campaign\records\CampaignTypeRecord;
use putyourlightson\campaign\records\ContactRecord;
use putyourlightson\campaign\records\MailingListTypeRecord;
use putyourlightson\campaign\records\SendoutRecord;

class m220312_120000_update_columns extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->alterColumn(ContactRecord::tableName(), 'email', $this->string());
        $this->alterColumn(SendoutRecord::tableName(), 'sendStatus', $this->string());
        $this->alterColumn(SendoutRecord::tableName(), 'fromName', $this->string());
        $this->alterColumn(SendoutRecord::tableName(), 'fromEmail', $this->string());

        if (!$this->db->columnExists(CampaignTypeRecord::tableName(), 'enableAnonymousTracking')) {
            $this->addColumn(
                CampaignTypeRecord::tableName(),
                'enableAnonymousTracking',
                $this->boolean()->defaultValue(false)->notNull()->after('handle'),
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
