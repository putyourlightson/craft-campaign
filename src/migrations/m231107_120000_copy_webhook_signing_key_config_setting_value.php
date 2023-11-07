<?php

namespace putyourlightson\campaign\migrations;

use Craft;
use craft\db\Migration;

class m231107_120000_copy_webhook_signing_key_config_setting_value extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $projectConfig = Craft::$app->getProjectConfig();

        // Don't make the same config changes twice
        $schemaVersion = $projectConfig->get('plugins.campaign.schemaVersion', true);

        if (version_compare($schemaVersion, '2.10.0', '<')) {
            $projectConfig->set(
                'plugins.campaign.settings.webhookSigningKey',
                $projectConfig->get('plugins.campaign.settings.mailgunWebhookSigningKey'),
            );
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
