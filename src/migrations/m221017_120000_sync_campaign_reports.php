<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;
use putyourlightson\campaign\Campaign;

class m221017_120000_sync_campaign_reports extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        Campaign::$plugin->reports->sync();

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
