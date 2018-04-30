<?php

namespace putyourlightson\campaign\migrations;

use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\records\ContactCampaignRecord;
use putyourlightson\campaign\records\ContactMailingListRecord;

use Craft;
use craft\db\Migration;
use craft\helpers\Json;

/**
 * m180430_120000_source_columns migration.
 */
class m180430_120000_source_columns extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        //$this->renameColumn('{{%campaign_contacts_mailinglists}}', 'source', 'sourceType');
        //$this->renameColumn('{{%campaign_contacts_mailinglists}}', 'sourceUrl', 'source');

        // Resave contact mailing lists
        $contactMailingLists = ContactMailingListRecord::find()
            ->where(['not', ['sourceType' => 'web']])
            ->all();

        foreach ($contactMailingLists as $contactMailingList) {
            /** @var ContactMailingListRecord $contactMailingList */
            $start = strrpos($contactMailingList->source, '/') + 1;
            $id = substr($contactMailingList->source, $start);
            $contactMailingList->source = is_numeric($id) ? $id : '';

            $contactMailingList->save();
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m180430_120000_source_columns cannot be reverted.\n";

        return false;
    }
}
