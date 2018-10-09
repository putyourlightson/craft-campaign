<?php

namespace putyourlightson\campaign\migrations;

use Craft;
use craft\db\Migration;
use putyourlightson\campaign\records\CampaignTypeRecord;
use putyourlightson\campaign\records\CampaignTypeSiteRecord;
use putyourlightson\campaign\records\MailingListTypeRecord;
use putyourlightson\campaign\records\MailingListTypeSiteModel;

class m181010_120000_add_sites_tables extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $sites = Craft::$app->getSites()->getAllSites();

        $table = CampaignTypeRecord::tableName();

        if (!$this->db->tableExists($table)) {
            $this->createTable($table, [
                'id' => $this->primaryKey(),
                'campaignTypeId' => $this->integer()->notNull(),
                'siteId' => $this->integer()->notNull(),
                'uriFormat' => $this->text(),
                'htmlTemplate' => $this->string(500),
                'plaintextTemplate' => $this->string(500),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);

            $records = CampaignTypeRecord::findAll();

            foreach ($records as $record) {
                foreach ($sites as $site) {
                    $newRecord = new CampaignTypeSiteRecord();
                    $newRecord->campaignTypeId = $record->id;
                    $newRecord->siteId = $site->id;
                    $newRecord->uriFormat = $record->uriFormat;
                    $newRecord->htmlTemplate = $record->htmlTemplate;
                    $newRecord->plaintextTemplate = $record->plaintextTemplate;
                    $newRecord->save();
                }
            }
        }

        $this->dropColumn($table, 'uriFormat');
        $this->dropColumn($table, 'htmlTemplate');
        $this->dropColumn($table, 'plaintextTemplate');


        $table = MailingListTypeRecord::tableName();

        if (!$this->db->tableExists($table)) {
            $this->createTable($table, [
                'id' => $this->primaryKey(),
                'mailingListTypeId' => $this->integer()->notNull(),
                'siteId' => $this->integer()->notNull(),
                'verifyEmailTemplate' => $this->string(500),
                'subscribeSuccessTemplate' => $this->string(500),
                'unsubscribeSuccessTemplate' => $this->string(500),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);

            $records = MailingListTypeRecord::findAll();

            foreach ($records as $record) {
                foreach ($sites as $site) {
                    $newRecord = new MailingListTypeSiteModel();
                    $newRecord->mailingListTypeId = $record->id;
                    $newRecord->siteId = $site->id;
                    $newRecord->verifyEmailTemplate = $record->verifyEmailTemplate;
                    $newRecord->subscribeSuccessTemplate = $record->subscribeSuccessTemplate;
                    $newRecord->unsubscribeSuccessTemplate = $record->unsubscribeSuccessTemplate;
                    $newRecord->save();
                }
            }
        }

        $this->dropColumn($table, 'verifyEmailTemplate');
        $this->dropColumn($table, 'verifySuccessTemplate');
        $this->dropColumn($table, 'subscribeSuccessTemplate');
        $this->dropColumn($table, 'unsubscribeSuccessTemplate');
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
