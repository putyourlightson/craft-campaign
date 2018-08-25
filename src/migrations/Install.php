<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\migrations;

use craft\base\Element;
use putyourlightson\campaign\elements\CampaignElement;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\elements\SegmentElement;
use putyourlightson\campaign\elements\SendoutElement;

use Craft;
use craft\db\Migration;

/**
 * Campaign Install Migration
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0   
 */
class Install extends Migration
{
    // Public Properties
    // =========================================================================

    /**
     * @var string The database driver to use
     */
    public $driver;

    // Public Methods
    // =========================================================================

    /**
     * @return boolean
     */
    public function safeUp(): bool
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;

        if ($this->createTables()) {
            $this->createIndexes();
            $this->addForeignKeys();

            // Refresh the db schema caches
            Craft::$app->db->schema->refresh();
        }

        return true;
    }

    /**
     * @return boolean
     * @throws \Throwable
     */
    public function safeDown(): bool
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        $this->deleteElements();
        $this->deleteFieldLayouts();
        $this->deleteTables();

        return true;
    }

    // Protected Methods
    // =========================================================================

    /**
     * Creates the tables needed for the Records used by the plugin
     *
     * @return boolean
     */
    protected function createTables(): bool
    {
        if (!$this->db->tableExists('{{%campaign_campaigns}}')) {
            $this->createTable('{{%campaign_campaigns}}', [
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

        if (!$this->db->tableExists('{{%campaign_campaigntypes}}')) {
            $this->createTable('{{%campaign_campaigntypes}}', [
                'id' => $this->primaryKey(),
                'fieldLayoutId' => $this->integer(),
                'name' => $this->string()->notNull(),
                'handle' => $this->string()->notNull(),
                'htmlTemplate' => $this->string(),
                'plaintextTemplate' => $this->string(),
                'uriFormat' => $this->string(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);
        }

        if (!$this->db->tableExists('{{%campaign_links}}')) {
            $this->createTable('{{%campaign_links}}', [
                'id' => $this->primaryKey(),
                'lid' => $this->uid(),
                'campaignId' => $this->integer()->notNull(),
                'url' => $this->string()->notNull(),
                'title' => $this->string()->notNull(),
                'clicked' => $this->integer()->defaultValue(0)->notNull(),
                'clicks' => $this->integer()->defaultValue(0)->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);
        }

        if (!$this->db->tableExists('{{%campaign_contacts}}')) {
            $this->createTable('{{%campaign_contacts}}', [
                'id' => $this->primaryKey(),
                'cid' => $this->uid(),
                'email' => $this->string()->notNull(),
                'country' => $this->string(),
                'geoIp' => $this->text(),
                'device' => $this->string(),
                'os' => $this->string(),
                'client' => $this->string(),
                'lastActivity' => $this->dateTime(),
                'complained' => $this->dateTime(),
                'bounced' => $this->dateTime(),
                'verified' => $this->dateTime(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);
        }

        if (!$this->db->tableExists('{{%campaign_pendingcontacts}}')) {
            $this->createTable('{{%campaign_pendingcontacts}}', [
                'id' => $this->primaryKey(),
                'pid' => $this->uid(),
                'email' => $this->string()->notNull(),
                'mailingListId' => $this->integer()->notNull(),
                'source' => $this->string(),
                'fieldData' => $this->text(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);
        }

        if (!$this->db->tableExists('{{%campaign_contacts_campaigns}}')) {
            $this->createTable('{{%campaign_contacts_campaigns}}', [
                'id' => $this->primaryKey(),
                'contactId' => $this->integer()->notNull(),
                'campaignId' => $this->integer()->notNull(),
                'sendoutId' => $this->integer()->notNull(),
                'mailingListId' => $this->integer()->notNull(),
                'sent' => $this->dateTime(),
                'failed' => $this->dateTime(),
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

        if (!$this->db->tableExists('{{%campaign_mailinglists}}')) {
            $this->createTable('{{%campaign_mailinglists}}', [
                'id' => $this->primaryKey(),
                'mailingListTypeId' => $this->integer()->notNull(),
                'syncedUserGroupId' => $this->integer(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);
        }

        if (!$this->db->tableExists('{{%campaign_mailinglisttypes}}')) {
            $this->createTable('{{%campaign_mailinglisttypes}}', [
                'id' => $this->primaryKey(),
                'fieldLayoutId' => $this->integer(),
                'name' => $this->string()->notNull(),
                'handle' => $this->string()->notNull(),
                'doubleOptIn' => $this->boolean()->defaultValue(true)->notNull(),
                'verifyEmailTemplate' => $this->string(),
                'verifySuccessTemplate' => $this->string(),
                'subscribeSuccessTemplate' => $this->string(),
                'unsubscribeSuccessTemplate' => $this->string(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);
        }

        if (!$this->db->tableExists('{{%campaign_contacts_mailinglists}}')) {
            $this->createTable('{{%campaign_contacts_mailinglists}}', [
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

        if (!$this->db->tableExists('{{%campaign_segments}}')) {
            $this->createTable('{{%campaign_segments}}', [
                'id' => $this->primaryKey(),
                'conditions' => $this->text(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);
        }

        if (!$this->db->tableExists('{{%campaign_sendouts}}')) {
            $this->createTable('{{%campaign_sendouts}}', [
                'id' => $this->primaryKey(),
                'sid' => $this->uid(),
                'campaignId' => $this->integer(),
                'senderId' => $this->integer(),
                'sendoutType' => $this->string()->notNull(),
                'sendStatus' => $this->string()->notNull(),
                'sendStatusMessage' => $this->text(),
                'fromName' => $this->string()->notNull(),
                'fromEmail' => $this->string()->notNull(),
                'subject' => $this->string()->notNull(),
                'notificationEmailAddress' => $this->string(),
                'googleAnalyticsLinkTracking' => $this->boolean()->defaultValue(false)->notNull(),
                'mailingListIds' => $this->text(),
                'excludedMailingListIds' => $this->text(),
                'segmentIds' => $this->text(),
                'recipients' => $this->integer()->defaultValue(0)->notNull(),
                'failedRecipients' => $this->integer()->defaultValue(0)->notNull(),
                'schedule' => $this->text(),
                'htmlBody' => $this->text(),
                'plaintextBody' => $this->text(),
                'sendDate' => $this->dateTime(),
                'lastSent' => $this->dateTime(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);
        }

        if (!$this->db->tableExists('{{%campaign_imports}}')) {
            $this->createTable('{{%campaign_imports}}', [
                'id' => $this->primaryKey(),
                'fileName' => $this->string(),
                'filePath' => $this->string(),
                'userGroupId' => $this->integer(),
                'userId' => $this->integer(),
                'mailingListId' => $this->integer(),
                'added' => $this->integer(),
                'updated' => $this->integer(),
                'failed' => $this->integer(),
                'failures' => $this->text(),
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
     *
     * @return void
     */
    protected function createIndexes()
    {
        $this->createIndex(null, '{{%campaign_campaigntypes}}', 'handle', true);
        $this->createIndex(null, '{{%campaign_contacts}}', 'email', true);
        $this->createIndex(null, '{{%campaign_contacts}}', 'cid', true);
        $this->createIndex(null, '{{%campaign_pendingcontacts}}', 'pid', true);
        $this->createIndex(null, '{{%campaign_pendingcontacts}}', 'email, mailingListId', false);
        $this->createIndex(null, '{{%campaign_contacts_campaigns}}', 'contactId, sendoutId', true);
        $this->createIndex(null, '{{%campaign_contacts_mailinglists}}', 'contactId, mailingListId', true);
        $this->createIndex(null, '{{%campaign_contacts_mailinglists}}', 'subscriptionStatus', false);
        $this->createIndex(null, '{{%campaign_links}}', 'lid', true);
        $this->createIndex(null, '{{%campaign_mailinglisttypes}}', 'handle', true);
        $this->createIndex(null, '{{%campaign_sendouts}}', 'sid', true);
        $this->createIndex(null, '{{%campaign_sendouts}}', 'sendoutType', false);
        $this->createIndex(null, '{{%campaign_sendouts}}', 'sendStatus', false);
    }

    /**
     * Creates the foreign keys needed for the Records used by the plugin
     *
     * @return void
     */
    protected function addForeignKeys()
    {
        $this->addForeignKey(null, '{{%campaign_campaigns}}', 'id', '{{%elements}}', 'id', 'CASCADE');
        $this->addForeignKey(null, '{{%campaign_campaigns}}', 'campaignTypeId', '{{%campaign_campaigntypes}}', 'id', 'CASCADE');
        $this->addForeignKey(null, '{{%campaign_campaigntypes}}', 'fieldLayoutId', '{{%fieldlayouts}}', 'id', 'SET NULL', null);
        $this->addForeignKey(null, '{{%campaign_contacts}}', 'id', '{{%elements}}', 'id', 'CASCADE');
        $this->addForeignKey(null, '{{%campaign_contacts_campaigns}}', 'contactId', '{{%campaign_contacts}}', 'id', 'CASCADE');
        $this->addForeignKey(null, '{{%campaign_contacts_campaigns}}', 'campaignId', '{{%campaign_campaigns}}', 'id', 'CASCADE');
        $this->addForeignKey(null, '{{%campaign_contacts_mailinglists}}', 'contactId', '{{%campaign_contacts}}', 'id', 'CASCADE');
        $this->addForeignKey(null, '{{%campaign_contacts_mailinglists}}', 'mailingListId', '{{%campaign_mailinglists}}', 'id', 'CASCADE');
        $this->addForeignKey(null, '{{%campaign_imports}}', 'userId', '{{%users}}', 'id', 'SET NULL');
        $this->addForeignKey(null, '{{%campaign_links}}', 'campaignId', '{{%campaign_campaigns}}', 'id', 'CASCADE');
        $this->addForeignKey(null, '{{%campaign_mailinglists}}', 'id', '{{%elements}}', 'id', 'CASCADE');
        $this->addForeignKey(null, '{{%campaign_mailinglists}}', 'mailingListTypeId', '{{%campaign_mailinglisttypes}}', 'id', 'CASCADE');
        $this->addForeignKey(null, '{{%campaign_mailinglists}}', 'syncedUserGroupId', '{{%usergroups}}', 'id', 'SET NULL');
        $this->addForeignKey(null, '{{%campaign_mailinglisttypes}}', 'fieldLayoutId', '{{%fieldlayouts}}', 'id', 'SET NULL');
        $this->addForeignKey(null, '{{%campaign_segments}}', 'id', '{{%elements}}', 'id', 'CASCADE');
        $this->addForeignKey(null, '{{%campaign_sendouts}}', 'id', '{{%elements}}', 'id', 'CASCADE');
        $this->addForeignKey(null, '{{%campaign_sendouts}}', 'campaignId', '{{%campaign_campaigns}}', 'id', 'CASCADE');
        $this->addForeignKey(null, '{{%campaign_sendouts}}', 'senderId', '{{%users}}', 'id', 'SET NULL');
    }

    /**
     * Delete elements
     *
     * @return void
     * @throws \Throwable
     */
    protected function deleteElements()
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
            /* @var Element $elementType */
            $elements = $elementType::findAll();

            foreach ($elements as $element) {
                $elementsService->deleteElement($element);
            }
        }
    }

    /**
     * Delete field layouts
     *
     * @return void
     */
    protected function deleteFieldLayouts()
    {
        Craft::$app->fields->deleteLayoutsByType(CampaignElement::class);
        Craft::$app->fields->deleteLayoutsByType(MailingListElement::class);
        Craft::$app->fields->deleteLayoutsByType(ContactElement::class);
    }

    /**
    * Delete tables
    *
    * @return void
    */
    protected function deleteTables()
    {
        // Drop tables with foreign keys first
        $this->dropTableIfExists('{{%campaign_sendouts}}');
        $this->dropTableIfExists('{{%campaign_contacts_campaigns}}');
        $this->dropTableIfExists('{{%campaign_links}}');
        $this->dropTableIfExists('{{%campaign_campaigns}}');
        $this->dropTableIfExists('{{%campaign_campaigntypes}}');
        $this->dropTableIfExists('{{%campaign_contacts_mailinglists}}');
        $this->dropTableIfExists('{{%campaign_mailinglists}}');
        $this->dropTableIfExists('{{%campaign_mailinglisttypes}}');
        $this->dropTableIfExists('{{%campaign_segments}}');
        $this->dropTableIfExists('{{%campaign_contacts}}');
        $this->dropTableIfExists('{{%campaign_imports}}');
    }
}
