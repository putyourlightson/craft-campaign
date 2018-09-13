<?php

namespace putyourlightson\campaign\migrations;

use putyourlightson\campaign\records\ContactMailingListRecord;

use craft\db\Migration;

class m180430_120000_source_columns extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->renameColumn('{{%campaign_contacts_mailinglists}}', 'source', 'sourceType');
        $this->renameColumn('{{%campaign_contacts_mailinglists}}', 'sourceUrl', 'source');

        // Resave contact mailing lists
        $contactMailingLists = ContactMailingListRecord::find()
            ->where(['not', ['sourceType' => 'web']])
            ->all();

        foreach ($contactMailingLists as $contactMailingList) {
            /** @var ContactMailingListRecord $contactMailingList */
            $start = strrpos($contactMailingList->source, '/') + 1;
            $id = substr($contactMailingList->source, $start);
            $contactMailingList->source = is_numeric($id) ? $id : 1;

            $contactMailingList->save();
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
