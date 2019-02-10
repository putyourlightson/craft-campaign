<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;
use craft\helpers\Json;
use craft\records\Plugin;
use putyourlightson\campaign\Campaign;

class m181014_120000_add_fromnamesemails_setting extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Get old plugin settings from database
        /** @var Plugin|null $plugin */
        $plugin = Plugin::find()
            ->where(['handle' => 'campaign'])
            ->one();

        if ($plugin !== null && !empty($plugin->settings)) {
            /** @var string $oldSettingsRaw */
            $oldSettingsRaw = $plugin->settings;
            $oldSettings = Json::decode($oldSettingsRaw);

            $settings = Campaign::$plugin->getSettings();
            $settings->fromNamesEmails = [[$oldSettings['defaultFromName'], $oldSettings['defaultFromEmail']]];

            Campaign::$plugin->settings->saveSettings($settings);
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
