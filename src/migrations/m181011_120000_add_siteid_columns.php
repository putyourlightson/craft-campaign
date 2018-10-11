<?php

namespace putyourlightson\campaign\migrations;

use Craft;
use craft\db\Migration;
use putyourlightson\campaign\records\CampaignTypeRecord;
use putyourlightson\campaign\records\MailingListTypeRecord;

class m181011_120000_add_siteid_columns extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $siteId = Craft::$app->getSites()->getPrimarySite()->id;

        if (!$this->db->columnExists('{{%campaign_campaigntypes}}', 'siteId')) {
            $this->addColumn('{{%campaign_campaigntypes}}', 'siteId', $this->integer()->notNull()->after('id'));

            $campaignTypeRecords = CampaignTypeRecord::find()->all();

            /** @var CampaignTypeRecord $campaignTypeRecord */
            foreach ($campaignTypeRecords as $campaignTypeRecord) {
                $campaignTypeRecord->siteId = $siteId;
                $campaignTypeRecord->save();
            }

            $this->addForeignKey(null, '{{%campaign_campaigntypes}}', 'siteId', '{{%sites}}', 'id', 'CASCADE');
        }

        if (!$this->db->columnExists('{{%campaign_mailinglisttypes}}', 'siteId')) {
            $this->addColumn('{{%campaign_mailinglisttypes}}', 'siteId', $this->integer()->notNull()->after('id'));

            $mailingListTypeRecords = MailingListTypeRecord::find()->all();

            /** @var MailingListTypeRecord $mailingListTypeRecord */
            foreach ($mailingListTypeRecords as $mailingListTypeRecord) {
                $mailingListTypeRecord->siteId = $siteId;
                $mailingListTypeRecord->save();
            }

            $this->addForeignKey(null, '{{%campaign_mailinglisttypes}}', 'siteId', '{{%sites}}', 'id', 'CASCADE');
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
