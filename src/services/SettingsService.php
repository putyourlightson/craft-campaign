<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\services;

use craft\base\Volume;
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
     * Gets whether the `@web` alias is used in the base URL of any site or volume
     *
     * @return bool
     */
    public function getWebAliasUsed(): bool
    {
        $sites = Craft::$app->getSites()->getAllSites();

        foreach ($sites as $site) {
            if (stripos($site->baseUrl, '@web') !== false) {
                 return true;
            }
        }

        $volumes = Craft::$app->getVolumes()->getAllVolumes();

        /** @var Volume $volume */
        foreach ($volumes as $volume) {
            if (stripos($volume->url, '@web') !== false) {
                 return true;
            }
        }

        return false;
    }

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