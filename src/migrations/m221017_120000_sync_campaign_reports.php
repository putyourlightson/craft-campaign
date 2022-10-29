<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;
use putyourlightson\campaign\records\CampaignRecord;
use putyourlightson\campaign\records\ContactCampaignRecord;

class m221017_120000_sync_campaign_reports extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        /** @var CampaignRecord[] $campaignRecords */
        $campaignRecords = CampaignRecord::find()
            ->where(['>', 'recipients', '0'])
            ->all();

        foreach ($campaignRecords as $campaignRecord) {
            $campaignRecord->opened = $this->_getCampaignColumnCount($campaignRecord->id, 'opened');
            $campaignRecord->clicked = $this->_getCampaignColumnCount($campaignRecord->id, 'clicked');
            $campaignRecord->opens = $this->_getCampaignColumnSum($campaignRecord->id, 'opens');
            $campaignRecord->clicks = $this->_getCampaignColumnSum($campaignRecord->id, 'clicks');
            $campaignRecord->save();
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

    private function _getCampaignColumnCount(int $campaignId, string $column): int
    {
        return (int)ContactCampaignRecord::find()
            ->where(['campaignId' => $campaignId])
            ->andWhere(['not', [$column => null]])
            ->count();
    }

    private function _getCampaignColumnSum(int $campaignId, string $column): int
    {
        return (int)ContactCampaignRecord::find()
            ->where(['campaignId' => $campaignId])
            ->sum($column);
    }
}
