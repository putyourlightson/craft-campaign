<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;
use putyourlightson\campaign\records\ContactRecord;
use putyourlightson\campaign\records\LinkRecord;
use putyourlightson\campaign\records\PendingContactRecord;
use putyourlightson\campaign\records\SendoutRecord;

class m200411_120000_update_short_uid_columns extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $install = new Install();

        if ($this->db->columnExists(LinkRecord::tableName(), 'lid')) {
            $this->alterColumn(LinkRecord::tableName(), 'lid', $install->shortUid());
        }

        if ($this->db->columnExists(ContactRecord::tableName(), 'cid')) {
            $this->alterColumn(ContactRecord::tableName(), 'cid', $install->shortUid());
        }

        if ($this->db->columnExists(PendingContactRecord::tableName(), 'pid')) {
            $this->alterColumn(PendingContactRecord::tableName(), 'pid', $install->shortUid());
        }

        if ($this->db->columnExists(SendoutRecord::tableName(), 'sid')) {
            $this->alterColumn(SendoutRecord::tableName(), 'sid', $install->shortUid());
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
