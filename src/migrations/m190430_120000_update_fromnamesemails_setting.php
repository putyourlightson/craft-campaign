<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;
use putyourlightson\campaign\Campaign;

class m190430_120000_update_fromnamesemails_setting extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $settings = Campaign::$plugin->getSettings();

        if (is_array($settings->fromNamesEmails)) {
            foreach ($settings->fromNamesEmails as &$row) {
                $row[3] = $row[2] ?? '';
                $row[2] = '';
            }
        }

        Campaign::$plugin->settings->saveSettings($settings);

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
