<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\records\ImportRecord;
use putyourlightson\campaign\records\SendoutRecord;

class m220427_120000_update_columns extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->renameColumn(ImportRecord::tableName(), 'fails', 'failures');
        $this->renameColumn(SendoutRecord::tableName(), 'fails', 'failures');

        if ($this->db->columnExists(SendoutRecord::tableName(), 'notificationEmailAddress')) {
            $this->addColumn(
                SendoutRecord::tableName(),
                'notificationContactIds',
                $this->text()->after('notificationEmailAddress'),
            );

            /** @var SendoutRecord[] $sendoutRecords */
            $sendoutRecords = SendoutRecord::find()
                ->where(['not', ['notificationEmailAddress' => null]])
                ->all();

            foreach ($sendoutRecords as $sendoutRecord) {
                $contact = Campaign::$plugin->contacts->getContactByEmail($sendoutRecord->getAttribute('notificationEmailAddress'));

                if ($contact !== null) {
                    $sendoutRecord->notificationContactIds = [$contact->id];
                    $sendoutRecord->save();
                }
            }

            $this->dropColumn(SendoutRecord::tableName(), 'notificationEmailAddress');
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
