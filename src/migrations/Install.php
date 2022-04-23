<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\migrations;

use Craft;
use craft\base\Element;
use craft\db\Migration;
use craft\db\Table;
use putyourlightson\campaign\elements\CampaignElement;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\elements\SegmentElement;
use putyourlightson\campaign\elements\SendoutElement;
use putyourlightson\campaign\records\CampaignRecord;
use putyourlightson\campaign\records\CampaignTypeRecord;
use putyourlightson\campaign\records\ContactCampaignRecord;
use putyourlightson\campaign\records\ContactMailingListRecord;
use putyourlightson\campaign\records\ContactRecord;
use putyourlightson\campaign\records\ImportRecord;
use putyourlightson\campaign\records\LinkRecord;
use putyourlightson\campaign\records\MailingListRecord;
use putyourlightson\campaign\records\MailingListTypeRecord;
use putyourlightson\campaign\records\PendingContactRecord;
use putyourlightson\campaign\records\SegmentRecord;
use putyourlightson\campaign\records\SendoutRecord;
use yii\db\ColumnSchemaBuilder;

class Install extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if ($this->createTables()) {
            $this->createIndexes();
            $this->addForeignKeys();

            // Refresh the db schema caches
            Craft::$app->getDb()->schema->refresh();
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->deleteElements();
        $this->deleteFieldLayouts();
        $this->deleteTables();
        $this->deleteProjectConfig();

        return true;
    }

    /**
     * Returns a short UID column.
     */
    public function shortUid(): ColumnSchemaBuilder
    {
        return $this->char(17)->notNull()->defaultValue('0');
    }

    /**
     * Creates the tables needed for the records used by the plugin.
     */
    protected function createTables(): bool
    {
        if (!$this->db->tableExists(CampaignRecord::tableName())) {
            $this->createTable(CampaignRecord::tableName(), [
                'id' => $this->primaryKey(),
                'campaignTypeId' => $this->integer()->notNull(),
                'recipients' => $this->integer()->defaultValue(0)->notNull(),
                'opened' => $this->integer()->defaultValue(0)->notNull(),
                'clicked' => $this->integer()->defaultValue(0)->notNull(),
                'opens' => $this->integer()->defaultValue(0)->notNull(),
                'clicks' => $this->integer()->defaultValue(0)->notNull(),
                'unsubscribed' => $this->integer()->defaultValue(0)->notNull(),
                'complained' => $this->integer()->defaultValue(0)->notNull(),
                'bounced' => $this->integer()->defaultValue(0)->notNull(),
                'dateClosed' => $this->dateTime(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);
        }

        if (!$this->db->tableExists(CampaignTypeRecord::tableName())) {
            $this->createTable(CampaignTypeRecord::tableName(), [
                'id' => $this->primaryKey(),
                'siteId' => $this->integer()->notNull(),
                'fieldLayoutId' => $this->integer(),
                'name' => $this->string()->notNull(),
                'handle' => $this->string()->notNull(),
                'uriFormat' => $this->text(),
                'htmlTemplate' => $this->string(500),
                'plaintextTemplate' => $this->string(500),
                'queryStringParameters' => $this->text(),
                'testContactIds' => $this->text(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);
        }

        if (!$this->db->tableExists(LinkRecord::tableName())) {
            $this->createTable(LinkRecord::tableName(), [
                'id' => $this->primaryKey(),
                'lid' => $this->shortUid(),
                'campaignId' => $this->integer()->notNull(),
                'url' => $this->text(),
                'title' => $this->string()->notNull(),
                'clicked' => $this->integer()->defaultValue(0)->notNull(),
                'clicks' => $this->integer()->defaultValue(0)->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);
        }

        if (!$this->db->tableExists(ContactRecord::tableName())) {
            $this->createTable(ContactRecord::tableName(), [
                'id' => $this->primaryKey(),
                'userId' => $this->integer(),
                'cid' => $this->shortUid(),
                'email' => $this->string(),
                'country' => $this->string(),
                'geoIp' => $this->text(),
                'device' => $this->string(),
                'os' => $this->string(),
                'client' => $this->string(),
                'lastActivity' => $this->dateTime(),
                'verified' => $this->dateTime(),
                'complained' => $this->dateTime(),
                'bounced' => $this->dateTime(),
                'blocked' => $this->dateTime(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);
        }

        if (!$this->db->tableExists(PendingContactRecord::tableName())) {
            $this->createTable(PendingContactRecord::tableName(), [
                'id' => $this->primaryKey(),
                'pid' => $this->shortUid(),
                'email' => $this->string()->notNull(),
                'mailingListId' => $this->integer()->notNull(),
                'source' => $this->string(),
                'fieldData' => $this->text(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'dateDeleted' => $this->dateTime()->null(),
                'uid' => $this->uid(),
            ]);
        }

        if (!$this->db->tableExists(ContactCampaignRecord::tableName())) {
            $this->createTable(ContactCampaignRecord::tableName(), [
                'id' => $this->primaryKey(),
                'contactId' => $this->integer()->notNull(),
                'campaignId' => $this->integer()->notNull(),
                'sendoutId' => $this->integer()->notNull(),
                'mailingListId' => $this->integer(),
                'sent' => $this->dateTime(),
                'opened' => $this->dateTime(),
                'clicked' => $this->dateTime(),
                'unsubscribed' => $this->dateTime(),
                'complained' => $this->dateTime(),
                'bounced' => $this->dateTime(),
                'opens' => $this->integer()->defaultValue(0)->notNull(),
                'clicks' => $this->integer()->defaultValue(0)->notNull(),
                'links' => $this->text(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);
        }

        if (!$this->db->tableExists(MailingListRecord::tableName())) {
            $this->createTable(MailingListRecord::tableName(), [
                'id' => $this->primaryKey(),
                'mailingListTypeId' => $this->integer()->notNull(),
                'syncedUserGroupId' => $this->integer(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);
        }

        if (!$this->db->tableExists(MailingListTypeRecord::tableName())) {
            $this->createTable(MailingListTypeRecord::tableName(), [
                'id' => $this->primaryKey(),
                'siteId' => $this->integer()->notNull(),
                'fieldLayoutId' => $this->integer(),
                'name' => $this->string()->notNull(),
                'handle' => $this->string()->notNull(),
                'subscribeVerificationRequired' => $this->boolean()->defaultValue(true)->notNull(),
                'subscribeVerificationEmailSubject' => $this->text(),
                'subscribeVerificationEmailTemplate' => $this->string(500),
                'subscribeSuccessTemplate' => $this->string(500),
                'unsubscribeFormAllowed' => $this->boolean()->defaultValue(false)->notNull(),
                'unsubscribeVerificationEmailSubject' => $this->text(),
                'unsubscribeVerificationEmailTemplate' => $this->string(500),
                'unsubscribeSuccessTemplate' => $this->string(500),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);
        }

        if (!$this->db->tableExists(ContactMailingListRecord::tableName())) {
            $this->createTable(ContactMailingListRecord::tableName(), [
                'id' => $this->primaryKey(),
                'contactId' => $this->integer()->notNull(),
                'mailingListId' => $this->integer()->notNull(),
                'subscriptionStatus' => $this->string()->notNull(),
                'subscribed' => $this->dateTime(),
                'unsubscribed' => $this->dateTime(),
                'complained' => $this->dateTime(),
                'bounced' => $this->dateTime(),
                'verified' => $this->dateTime(),
                'sourceType' => $this->string(),
                'source' => $this->string(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);
        }

        if (!$this->db->tableExists(SegmentRecord::tableName())) {
            $this->createTable(SegmentRecord::tableName(), [
                'id' => $this->primaryKey(),
                'segmentType' => $this->string()->notNull(),
                'conditions' => $this->text(),
                'template' => $this->text(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);
        }

        if (!$this->db->tableExists(SendoutRecord::tableName())) {
            $this->createTable(SendoutRecord::tableName(), [
                'id' => $this->primaryKey(),
                'sid' => $this->shortUid(),
                'campaignId' => $this->integer(),
                'senderId' => $this->integer(),
                'sendoutType' => $this->string()->notNull(),
                'sendStatus' => $this->string(),
                'fromName' => $this->string(),
                'fromEmail' => $this->string(),
                'replyToEmail' => $this->string(),
                'subject' => $this->text(),
                'notificationEmailAddress' => $this->string(),
                'contactIds' => $this->text(),
                'failedContactIds' => $this->text(),
                'mailingListIds' => $this->text(),
                'excludedMailingListIds' => $this->text(),
                'segmentIds' => $this->text(),
                'recipients' => $this->integer()->defaultValue(0)->notNull(),
                'fails' => $this->integer()->defaultValue(0)->notNull(),
                'schedule' => $this->text(),
                'htmlBody' => $this->mediumText(),
                'plaintextBody' => $this->mediumText(),
                'sendDate' => $this->dateTime(),
                'lastSent' => $this->dateTime(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);
        }

        if (!$this->db->tableExists(ImportRecord::tableName())) {
            $this->createTable(ImportRecord::tableName(), [
                'id' => $this->primaryKey(),
                'assetId' => $this->integer(),
                'fileName' => $this->string(),
                'filePath' => $this->string(),
                'userGroupId' => $this->integer(),
                'userId' => $this->integer(),
                'mailingListId' => $this->integer(),
                'forceSubscribe' => $this->boolean()->defaultValue(false)->notNull(),
                'emailFieldIndex' => $this->string(),
                'fieldIndexes' => $this->text(),
                'added' => $this->integer(),
                'updated' => $this->integer(),
                'fails' => $this->integer(),
                'dateImported' => $this->dateTime(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);
        }

        return true;
    }

    /**
     * Creates the indexes needed for the Records used by the plugin
     */
    protected function createIndexes(): void
    {
        $this->createIndex(null, CampaignTypeRecord::tableName(), 'handle', true);
        $this->createIndex(null, ContactRecord::tableName(), 'email');
        $this->createIndex(null, ContactRecord::tableName(), 'cid');
        $this->createIndex(null, PendingContactRecord::tableName(), 'pid', true);
        $this->createIndex(null, PendingContactRecord::tableName(), 'email, mailingListId');
        $this->createIndex(null, ContactCampaignRecord::tableName(), 'contactId, sendoutId', true);
        $this->createIndex(null, ContactMailingListRecord::tableName(), 'contactId, mailingListId', true);
        $this->createIndex(null, ContactMailingListRecord::tableName(), 'subscriptionStatus');
        $this->createIndex(null, LinkRecord::tableName(), 'lid', true);
        $this->createIndex(null, MailingListTypeRecord::tableName(), 'handle', true);
        $this->createIndex(null, SegmentRecord::tableName(), 'segmentType');
        $this->createIndex(null, SendoutRecord::tableName(), 'sid');
        $this->createIndex(null, SendoutRecord::tableName(), 'sendoutType');
        $this->createIndex(null, SendoutRecord::tableName(), 'sendStatus');
    }

    /**
     * Creates the foreign keys needed for the records used by the plugin.
     */
    protected function addForeignKeys(): void
    {
        $this->addForeignKey(null, CampaignRecord::tableName(), 'id', Table::ELEMENTS, 'id', 'CASCADE');
        $this->addForeignKey(null, CampaignRecord::tableName(), 'campaignTypeId', CampaignTypeRecord::tableName(), 'id', 'CASCADE');
        $this->addForeignKey(null, CampaignTypeRecord::tableName(), 'siteId', Table::SITES, 'id', 'CASCADE');
        $this->addForeignKey(null, CampaignTypeRecord::tableName(), 'fieldLayoutId', Table::FIELDLAYOUTS, 'id', 'SET NULL');
        $this->addForeignKey(null, ContactRecord::tableName(), 'id', Table::ELEMENTS, 'id', 'CASCADE');
        $this->addForeignKey(null, ContactRecord::tableName(), 'userId', Table::USERS, 'id', 'CASCADE');
        $this->addForeignKey(null, ContactCampaignRecord::tableName(), 'contactId', ContactRecord::tableName(), 'id', 'CASCADE');
        $this->addForeignKey(null, ContactCampaignRecord::tableName(), 'campaignId', CampaignRecord::tableName(), 'id', 'CASCADE');
        $this->addForeignKey(null, ContactMailingListRecord::tableName(), 'contactId', ContactRecord::tableName(), 'id', 'CASCADE');
        $this->addForeignKey(null, ContactMailingListRecord::tableName(), 'mailingListId', MailingListRecord::tableName(), 'id', 'CASCADE');
        $this->addForeignKey(null, ImportRecord::tableName(), 'userId', Table::USERS, 'id', 'SET NULL');
        $this->addForeignKey(null, ImportRecord::tableName(), 'mailingListId', MailingListRecord::tableName(), 'id', 'SET NULL');
        $this->addForeignKey(null, LinkRecord::tableName(), 'campaignId', CampaignRecord::tableName(), 'id', 'CASCADE');
        $this->addForeignKey(null, MailingListRecord::tableName(), 'id', Table::ELEMENTS, 'id', 'CASCADE');
        $this->addForeignKey(null, MailingListRecord::tableName(), 'mailingListTypeId', MailingListTypeRecord::tableName(), 'id', 'CASCADE');
        $this->addForeignKey(null, MailingListRecord::tableName(), 'syncedUserGroupId', Table::USERGROUPS, 'id', 'SET NULL');
        $this->addForeignKey(null, MailingListTypeRecord::tableName(), 'siteId', Table::SITES, 'id', 'CASCADE');
        $this->addForeignKey(null, MailingListTypeRecord::tableName(), 'fieldLayoutId', Table::FIELDLAYOUTS, 'id', 'SET NULL');
        $this->addForeignKey(null, SegmentRecord::tableName(), 'id', Table::ELEMENTS, 'id', 'CASCADE');
        $this->addForeignKey(null, SendoutRecord::tableName(), 'id', Table::ELEMENTS, 'id', 'CASCADE');
        $this->addForeignKey(null, SendoutRecord::tableName(), 'campaignId', CampaignRecord::tableName(), 'id', 'CASCADE');
        $this->addForeignKey(null, SendoutRecord::tableName(), 'senderId', Table::USERS, 'id', 'SET NULL');
    }

    /**
     * Deletes elements.
     */
    protected function deleteElements(): void
    {
        $elementTypes = [
            CampaignElement::class,
            MailingListElement::class,
            ContactElement::class,
            SegmentElement::class,
            SendoutElement::class,
        ];

        $elementsService = Craft::$app->getElements();

        foreach ($elementTypes as $elementType) {
            /** @var Element $elementType */
            $elements = $elementType::findAll();

            foreach ($elements as $element) {
                $elementsService->deleteElement($element);
            }
        }
    }

    /**
     * Delete field layouts.
     */
    protected function deleteFieldLayouts(): void
    {
        Craft::$app->getFields()->deleteLayoutsByType(CampaignElement::class);
        Craft::$app->getFields()->deleteLayoutsByType(MailingListElement::class);
        Craft::$app->getFields()->deleteLayoutsByType(ContactElement::class);
    }

    /**
     * Deletes tables.
     */
    protected function deleteTables(): void
    {
        // Drop tables with foreign keys first
        $this->dropTableIfExists(SendoutRecord::tableName());
        $this->dropTableIfExists(ContactCampaignRecord::tableName());
        $this->dropTableIfExists(LinkRecord::tableName());
        $this->dropTableIfExists(CampaignRecord::tableName());
        $this->dropTableIfExists(CampaignTypeRecord::tableName());
        $this->dropTableIfExists(ContactMailingListRecord::tableName());
        $this->dropTableIfExists(ImportRecord::tableName());
        $this->dropTableIfExists(MailingListRecord::tableName());
        $this->dropTableIfExists(MailingListTypeRecord::tableName());
        $this->dropTableIfExists(SegmentRecord::tableName());
        $this->dropTableIfExists(ContactRecord::tableName());
        $this->dropTableIfExists(PendingContactRecord::tableName());
    }

    /**
     * Deletes project config.
     */
    protected function deleteProjectConfig(): void
    {
        Craft::$app->getProjectConfig()->remove('campaign');
    }
}
