<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\services;

use craft\base\Volume;
use craft\helpers\App;
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
     * Returns all of the sites as an array that can be used for options
     *
     * @return array
     */
    public function getSiteOptions(): array
    {
        $siteOptions = [];
        $sites = Craft::$app->getSites()->getAllSites();

        foreach ($sites as $site) {
            $siteOptions[$site->id] = $site->name;
        }

        return $siteOptions;
    }

    /**
     * Returns from name and email for the given site if provided
     *
     * @param int|null $siteId
     *
     * @return array
     */
    public function getFromNameEmail(int $siteId = null): array
    {
        // Get first from name and email
        $firstFromNameEmail = [];
        $fromNamesEmails = Campaign::$plugin->getSettings()->fromNamesEmails;

        foreach ($fromNamesEmails as $fromNameEmail) {
            if ($siteId === null || empty($fromNameEmail[2]) || $fromNameEmail[2] == $siteId) {
                $firstFromNameEmail = ['name' => $fromNameEmail[0], 'email' => $fromNameEmail[1]];

                break;
            }
        }

        // If still not set then default to system settings
        if (empty($firstFromNameEmail)) {
            $mailSettings = App::mailSettings();

            $firstFromNameEmail = [
                'name' => $mailSettings->fromName,
                'email' => $mailSettings->fromEmail,
            ];
        }

        return $firstFromNameEmail;
    }

    /**
     * Returns from names and emails that can be used for options for the given site if provided
     *
     * @param int|null $siteId
     *
     * @return array
     */
    public function getFromNameEmailOptions(int $siteId = null): array
    {
        $fromNameEmailOptions = [];
        $fromNamesEmails = Campaign::$plugin->getSettings()->fromNamesEmails;

        foreach ($fromNamesEmails as $fromNameEmail) {
            if ($siteId === null || empty($fromNameEmail[2]) || $fromNameEmail[2] == $siteId) {
                $fromNameEmailOptions[$fromNameEmail[0].':'.$fromNameEmail[1]] = $fromNameEmail[0].' <'.$fromNameEmail[1].'>';
            }
        }

        return $fromNameEmailOptions;
    }

    /**
     * Returns whether the `@web` alias is used in the URL of the site provided, or all sites, or asset volume
     *
     * @param int|null $siteId
     * @return bool
     */
    public function isWebAliasUsed(int $siteId = null): bool
    {
        $sites = [];

        if ($siteId !== null) {
            $site = Craft::$app->getSites()->getSiteById($siteId);

            if ($site !== null) {
                $sites[] = $site;
            }
        }
        else {
            $sites = Craft::$app->getSites()->getAllSites();
        }

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