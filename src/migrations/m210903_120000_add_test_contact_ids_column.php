<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;
use craft\helpers\MigrationHelper;
use putyourlightson\campaign\records\CampaignTypeRecord;
use yii\db\Expression;

class m210903_120000_add_test_contact_ids_column extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $table = CampaignTypeRecord::tableName();

        if ($this->db->columnExists($table, 'testContactId')) {
            if (!$this->db->columnExists($table, 'testContactIds')) {
                $this->addColumn($table, 'testContactIds', $this->text()->after('testContactId'));
            }

            $this->update($table, ['testContactIds' => new Expression('testContactId')]);

            /** @var CampaignTypeRecord[] $campaignTypeRecords */
            $campaignTypeRecords = CampaignTypeRecord::find()->all();

            foreach ($campaignTypeRecords as $campaignTypeRecord) {
                if ($campaignTypeRecord->testContactIds) {
                    $campaignTypeRecord->testContactIds = [$campaignTypeRecord->testContactIds];
                    $campaignTypeRecord->save();
                }
            }

            MigrationHelper::dropForeignKeyIfExists($table, 'testContactId');
            $this->dropColumn($table, 'testContactId');
        }

        return true;
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
