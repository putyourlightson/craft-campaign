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
    // Constants
    // =========================================================================

    /**
     * @event RegisterComponentTypesEvent The event that is triggered when registering mailer transport types.
     */
    const EVENT_REGISTER_MAILER_TRANSPORT_TYPES = 'registerMailerTransportTypes';

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

        return Craft::$app->plugins->savePluginSettings(Campaign::$plugin, $settings->getAttributes());
    }
}