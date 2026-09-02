<?php

use craft\helpers\App;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\helpers\SettingsHelper;
use putyourlightson\campaign\mail\CampaignMailer;

test('Craft email settings can be used for Campaign emails', function() {
    $settings = Campaign::$plugin->settings;
    $useCraftEmailSettings = $settings->useCraftEmailSettings;

    try {
        $settings->useCraftEmailSettings = true;
        $mailSettings = App::mailSettings();
        $site = Craft::$app->getSites()->getCurrentSite();
        $siteOverrides = version_compare(Craft::$app->getVersion(), '5.6.0', '>=') ? $mailSettings->siteOverrides : [];
        $overrides = $siteOverrides[$site->uid] ?? [];
        $fromNameEmail = SettingsHelper::getFromNameEmail($site->id);
        $mailer = Campaign::$plugin->createMailer($settings);

        expect($fromNameEmail)
            ->toBe([
                'name' => App::parseEnv($overrides['fromName'] ?? $mailSettings->fromName) ?? '',
                'email' => App::parseEnv($overrides['fromEmail'] ?? $mailSettings->fromEmail) ?? '',
                'replyTo' => App::parseEnv($overrides['replyToEmail'] ?? $mailSettings->replyToEmail) ?? '',
            ])
            ->and($mailer)
            ->toBeInstanceOf(CampaignMailer::class);
    } finally {
        $settings->useCraftEmailSettings = $useCraftEmailSettings;
    }
});
