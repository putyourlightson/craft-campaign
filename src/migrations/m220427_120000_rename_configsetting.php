<?php

namespace putyourlightson\campaign\migrations;

use Craft;
use craft\db\Migration;

class m220427_120000_rename_configsetting extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $projectConfig = Craft::$app->getProjectConfig();

        // Don't make the same config changes twice
        $schemaVersion = $projectConfig->get('plugins.campaign.schemaVersion', true);

        if (version_compare($schemaVersion, '2.0.0', '<')) {
            $maxSendFailuresAllowed = $projectConfig->get('campaign.settings.maxSendFailsAllowed', true) ?? 1;

            $projectConfig->set('campaign.settings.maxSendFailuresAllowed', $maxSendFailuresAllowed);
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
