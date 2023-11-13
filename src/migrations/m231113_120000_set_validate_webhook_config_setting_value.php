<?php

namespace putyourlightson\campaign\migrations;

use Craft;
use craft\db\Migration;

class m231113_120000_set_validate_webhook_config_setting_value extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $projectConfig = Craft::$app->getProjectConfig();

        // Don’t make the same config changes twice
        $schemaVersion = $projectConfig->get('plugins.campaign.schemaVersion', true);

        if (version_compare($schemaVersion, '2.10.0', '<')) {
            $signingKey = $projectConfig->get('plugins.campaign.settings.mailgunWebhookSigningKey');
            $projectConfig->set(
                'plugins.campaign.settings.validateWebhookRequests',
                !empty($signingKey),
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
