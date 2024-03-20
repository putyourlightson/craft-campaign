<?php

namespace putyourlightson\campaign\migrations;

use Craft;
use craft\db\Migration;
use putyourlightson\campaign\Campaign;

class m240320_120000_add_new_default_sendout_job_settings extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $projectConfig = Craft::$app->getProjectConfig();

        // Don’t make the same config changes twice
        $schemaVersion = $projectConfig->get('plugins.campaign.schemaVersion', true);

        if (version_compare($schemaVersion, '2.13.0', '<')) {
            // Copy over the deprecated setting values, if not set to the defaults.
            $maxBatchSize = Campaign::$plugin->settings->maxBatchSize;
            if ($maxBatchSize && $maxBatchSize !== 10000) {
                $projectConfig->set('plugins.campaign.settings.sendoutJobBatchSize', $maxBatchSize);
            }
            $batchJobDelay = Campaign::$plugin->settings->batchJobDelay;
            if ($batchJobDelay !== 10) {
                $projectConfig->set('plugins.campaign.settings.sendoutJobBatchDelay', $batchJobDelay);
            }
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
