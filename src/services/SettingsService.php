<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\services;

use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\models\SettingsModel;

use Craft;
use craft\helpers\Component;

/**
 * SettingsService
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 */
class SettingsService extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * Saves plugin settings
     *
     * @param SettingsModel $settings The plugin settings
     *
     * @return bool Whether the settings were saved successfully
     */
    public function saveSettings(SettingsModel $settings): bool
    {
        if (!$settings->validate()) {
            return false;
        }

        // Limit decimal places of floats
        $settings->memoryThreshold = number_format($settings->memoryThreshold, 2);
        $settings->timeThreshold = number_format($settings->timeThreshold, 2);

        return Craft::$app->plugins->savePluginSettings(Campaign::$plugin, $settings->getAttributes());
    }
}