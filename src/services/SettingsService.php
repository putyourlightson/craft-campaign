<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\services;

use Craft;
use craft\base\Component;
use craft\base\Field;
use craft\base\Volume;
use craft\events\CancelableEvent;
use craft\events\FieldEvent;
use craft\helpers\App;
use craft\helpers\ProjectConfig;
use craft\helpers\StringHelper;
use craft\mail\Mailer;
use craft\models\FieldLayout;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\models\SettingsModel;

/**
 * SettingsService
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 *
 * @property-read array $siteOptions
 * @property-read Mailer $mailerForVerificationEmails
 */
class SettingsService extends Component
{
    // Constants
    // =========================================================================

    /**
     * @since 1.15.0
     */
    const EVENT_BEFORE_SAVE_SETTINGS = 'beforeSaveSettings';

    /**
     * @since 1.15.0
     */
    const EVENT_AFTER_SAVE_SETTINGS = 'afterSaveSettings';

    /**
     * @since 1.15.0
     */
    const CONFIG_CONTACTFIELDLAYOUT_KEY = 'campaign.contactFieldLayout';

    // Public Methods
    // =========================================================================

    /**
     * Returns all the sites as an array that can be used for options
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
            if ($siteId === null || empty($fromNameEmail[3]) || $fromNameEmail[3] == $siteId) {
                $firstFromNameEmail = [
                    'name' => $fromNameEmail[0],
                    'email' => $fromNameEmail[1],
                    'replyTo' => $fromNameEmail[2],
                ];

                break;
            }
        }

        // If still not set then default to system settings
        if (empty($firstFromNameEmail)) {
            $mailSettings = App::mailSettings();

            $firstFromNameEmail = [
                'name' => $mailSettings->fromName,
                'email' => $mailSettings->fromEmail,
                'replyTo' => '',
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
            $fromSiteId = $fromNameEmail[3] ?? null;

            if ($siteId === null || $fromSiteId === null || $fromSiteId == $siteId) {
                $fromName = $fromNameEmail[0];
                $fromEmail = $fromNameEmail[1];
                $replyTo = $fromNameEmail[2] ?? '';

                $key = $fromName.':'.$fromEmail.':'.$replyTo;
                $value = $fromName.' <'.$fromEmail.'> ';

                if ($replyTo) {
                    $value .= Craft::t('campaign', '(reply to {email})', ['email' => $replyTo]);
                }

                $fromNameEmailOptions[$key] = $value;
            }
        }

        return $fromNameEmailOptions;
    }

    /**
     * Returns a mailer for sending verification emails
     *
     * @return Mailer
     * @since 1.22.0
     */
    public function getMailerForVerificationEmails(): Mailer
    {
        if (Campaign::$plugin->getSettings()->sendVerificationEmailsViaCraft) {
            return Craft::$app->mailer;
        }

        return Campaign::$plugin->mailer;
    }

    /**
     * Returns whether a dynamic `@web` alias is used in the URL of the site provided, or all sites, or asset volume
     *
     * @param int|null $siteId
     * @return bool
     */
    public function isDynamicWebAliasUsed(int $siteId = null): bool
    {
        if (!Craft::$app->getRequest()->isWebAliasSetDynamically) {
            return false;
        }

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
            // How this works was changed in 3.6.0
            // https://github.com/craftcms/cms/issues/3964#issuecomment-737546660
            if (version_compare(Craft::$app->getVersion(), '3.6.0', '>=')) {
                $unparsedBaseUrl = $site->getBaseUrl(false);
            }
            else {
                $unparsedBaseUrl = $site->baseUrl;
            }

            if (stripos($unparsedBaseUrl, '@web') !== false) {
                return true;
            }
        }

        $volumes = Craft::$app->getVolumes()->getAllVolumes();

        /** @var Volume $volume */
        foreach ($volumes as $volume) {
            if (stripos($volume->getRootUrl(), '@web') !== false) {
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
        // Limit decimal places of floats
        $settings->memoryThreshold = round($settings->memoryThreshold, 2);
        $settings->timeThreshold = round($settings->timeThreshold, 2);

        // Fire a before event
        $event = new CancelableEvent([
            'data' => $settings,
        ]);
        $this->trigger(self::EVENT_BEFORE_SAVE_SETTINGS, $event);

        if (!$event->isValid) {
            return false;
        }

        if (!$settings->validate()) {
            return false;
        }

        return Craft::$app->plugins->savePluginSettings(Campaign::$plugin, $settings->getAttributes());
    }
    /**
     * Saves the contact field layout
     *
     * @param FieldLayout $fieldLayout
     * @return bool
     * @since 1.15.0
     */
    public function saveContactFieldLayout(FieldLayout $fieldLayout): bool
    {
        $projectConfig = Craft::$app->getProjectConfig();
        $fieldLayoutConfig = $fieldLayout->getConfig();
        $uid = StringHelper::UUID();

        $projectConfig->set(self::CONFIG_CONTACTFIELDLAYOUT_KEY, [$uid => $fieldLayoutConfig], 'Save the contact field layout');

        return true;
    }

    /**
     * Handles a changed contact field layout.
     *
     * @since 1.15.0
     */
    public function handleChangedContactFieldLayout()
    {
        // Use this because we want this to trigger this if anything changes inside but ONLY ONCE
        static $parsed = false;
        if ($parsed) {
            return;
        }

        $parsed = true;
        $data = Craft::$app->getProjectConfig()->get(self::CONFIG_CONTACTFIELDLAYOUT_KEY, true);

        $fieldsService = Craft::$app->getFields();

        if (empty($data) || empty($config = reset($data))) {
            $fieldsService->deleteLayoutsByType(ContactElement::class);
            return;
        }

        // Make sure fields are processed
        ProjectConfig::ensureAllFieldsProcessed();

        // Save the field layout
        $layout = FieldLayout::createFromConfig($config);
        $layout->id = $fieldsService->getLayoutByType(ContactElement::class)->id;
        $layout->type = ContactElement::class;
        $layout->uid = key($data);
        $fieldsService->saveLayout($layout);
    }

    /**
     * Prunes a deleted field from the contact field layout.
     *
     * @param FieldEvent $event
     */
    public function pruneDeletedField(FieldEvent $event)
    {
        /** @var Field $field */
        $field = $event->field;
        $fieldUid = $field->uid;

        $projectConfig = Craft::$app->getProjectConfig();
        $fieldLayouts = $projectConfig->get(self::CONFIG_CONTACTFIELDLAYOUT_KEY);

        // Engage stealth mode
        $projectConfig->muteEvents = true;

        // Prune the field layout
        if (is_array($fieldLayouts)) {
            foreach ($fieldLayouts as $layoutUid => $layout) {
                if (!empty($layout['tabs'])) {
                    foreach ($layout['tabs'] as $tabUid => $tab) {
                        $projectConfig->remove(self::CONFIG_CONTACTFIELDLAYOUT_KEY . '.' . $layoutUid . '.tabs.' . $tabUid . '.fields.' . $fieldUid, 'Prune deleted field');
                    }
                }
            }
        }

        // Nuke all the layout fields from the DB
        Craft::$app->getDb()->createCommand()
            ->delete('{{%fieldlayoutfields}}', ['fieldId' => $field->id])
            ->execute();

        // Allow events again
        $projectConfig->muteEvents = false;
    }
}
