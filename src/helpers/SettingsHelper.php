<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\helpers;

use Craft;
use craft\helpers\App;
use craft\mail\Mailer;
use putyourlightson\campaign\Campaign;

/**
 * @property-read Mailer $mailerForVerificationEmails
 * @property-read array $siteOptions
 */
class SettingsHelper
{
    /**
     * Returns all the sites as an array that can be used for options.
     */
    public static function getSiteOptions(): array
    {
        $siteOptions = [];
        $sites = Craft::$app->getSites()->getAllSites();

        foreach ($sites as $site) {
            $siteOptions[$site->id] = $site->name;
        }

        return $siteOptions;
    }

    /**
     * Returns from name and email for the given site if provided.
     */
    public static function getFromNameEmail(int $siteId = null): array
    {
        // Get first from name and email
        $firstFromNameEmail = [];
        $fromNamesEmails = Campaign::$plugin->settings->fromNamesEmails;

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
     * Returns from names and emails that can be used for options for the given site if provided.
     */
    public static function getFromNameEmailOptions(int $siteId = null): array
    {
        $fromNameEmailOptions = [];
        $fromNamesEmails = Campaign::$plugin->settings->fromNamesEmails;

        foreach ($fromNamesEmails as $fromNameEmail) {
            $fromSiteId = $fromNameEmail[3] ?? null;

            if ($siteId === null || $fromSiteId === null || $fromSiteId == $siteId) {
                $fromName = $fromNameEmail[0];
                $fromEmail = $fromNameEmail[1];
                $replyTo = $fromNameEmail[2] ?? '';

                $key = $fromName . ':' . $fromEmail . ':' . $replyTo;
                $value = $fromName . ' <' . $fromEmail . '> ';

                if ($replyTo) {
                    $value .= Craft::t('campaign', '(reply to {email})', ['email' => $replyTo]);
                }

                $fromNameEmailOptions[$key] = $value;
            }
        }

        return $fromNameEmailOptions;
    }

    /**
     * Returns a mailer for sending verification emails.
     *
     * @since 1.22.0
     */
    public static function getMailerForVerificationEmails(): Mailer
    {
        if (Campaign::$plugin->settings->sendVerificationEmailsViaCraft) {
            return Craft::$app->getMailer();
        }

        return Campaign::$plugin->mailer;
    }

    /**
     * Returns whether a dynamic `@web` alias is used in the URL of the site provided
     * or all sites or a file system.
     */
    public static function isDynamicWebAliasUsed(int $siteId = null): bool
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
        } else {
            $sites = Craft::$app->getSites()->getAllSites();
        }

        foreach ($sites as $site) {
            $unparsedBaseUrl = $site->getBaseUrl(false);

            if (stripos($unparsedBaseUrl, '@web') !== false) {
                return true;
            }
        }

        $filesystems = Craft::$app->getFs()->getAllFilesystems();

        foreach ($filesystems as $filesystem) {
            if (stripos($filesystem->getRootUrl(), '@web') !== false) {
                return true;
            }
        }

        return false;
    }
}
