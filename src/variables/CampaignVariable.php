<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\variables;

use craft\helpers\Template;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\CampaignElement;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\db\CampaignElementQuery;
use putyourlightson\campaign\elements\db\ContactElementQuery;
use putyourlightson\campaign\elements\db\MailingListElementQuery;
use putyourlightson\campaign\elements\db\SegmentElementQuery;
use putyourlightson\campaign\elements\db\SendoutElementQuery;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\elements\SegmentElement;
use putyourlightson\campaign\elements\SendoutElement;
use putyourlightson\campaign\helpers\RecaptchaHelper;
use putyourlightson\campaign\helpers\StringHelper;
use putyourlightson\campaign\helpers\TurnstileHelper;
use putyourlightson\campaign\models\CampaignTypeModel;
use putyourlightson\campaign\models\ImportModel;
use putyourlightson\campaign\models\MailingListTypeModel;
use putyourlightson\campaign\models\SettingsModel;
use putyourlightson\campaign\services\ReportsService;
use Twig\Markup;

class CampaignVariable
{
    /**
     * Returns true if pro version.
     */
    public function getIsPro(): bool
    {
        return Campaign::$plugin->getIsPro();
    }

    /**
     * Throws an exception if the plugin edition is not pro.
     */
    public function requirePro(): void
    {
        Campaign::$plugin->requirePro();
    }

    /**
     * Returns a campaign element query.
     */
    public function getCampaigns(): CampaignElementQuery
    {
        return CampaignElement::find();
    }

    /**
     * Returns a contact element query.
     */
    public function getContacts(): ContactElementQuery
    {
        return ContactElement::find();
    }

    /**
     * Returns a mailing list element query.
     */
    public function getMailingLists(): MailingListElementQuery
    {
        return MailingListElement::find();
    }

    /**
     * Returns a segment element query.
     */
    public function getSegments(): SegmentElementQuery
    {
        return SegmentElement::find();
    }

    /**
     * Returns a sendout element query.
     */
    public function getSendouts(): SendoutElementQuery
    {
        return SendoutElement::find();
    }

    /**
     * Returns the reports service.
     */
    public function getReports(): ReportsService
    {
        return Campaign::$plugin->reports;
    }

    /**
     * Returns a campaign by ID.
     */
    public function getCampaignById(int $campaignId): ?CampaignElement
    {
        return Campaign::$plugin->campaigns->getCampaignById($campaignId);
    }

    /**
     * Returns all campaign types.
     */
    public function getAllCampaignTypes(): array
    {
        return Campaign::$plugin->campaignTypes->getAllCampaignTypes();
    }

    /**
     * Returns a campaign type by ID.
     */
    public function getCampaignTypeById(int $campaignTypeId): ?CampaignTypeModel
    {
        return Campaign::$plugin->campaignTypes->getCampaignTypeById($campaignTypeId);
    }

    /**
     * Returns a contact by ID.
     */
    public function getContactById(int $contactId): ?ContactElement
    {
        return Campaign::$plugin->contacts->getContactById($contactId);
    }

    /**
     * Returns a mailing list by ID.
     */
    public function getMailingListById(int $mailingListId): ?MailingListElement
    {
        return Campaign::$plugin->mailingLists->getMailingListById($mailingListId);
    }

    /**
     * Returns all mailing lists across all sites.
     */
    public function getAllMailingLists(): array
    {
        return Campaign::$plugin->mailingLists->getAllMailingLists();
    }

    /**
     * Returns all mailing list types.
     */
    public function getAllMailingListTypes(): array
    {
        return Campaign::$plugin->mailingListTypes->getAllMailingListTypes();
    }

    /**
     * Returns a mailing list type by ID.
     */
    public function getMailingListTypeById(int $mailingListTypeId): ?MailingListTypeModel
    {
        return Campaign::$plugin->mailingListTypes->getMailingListTypeById($mailingListTypeId);
    }

    /**
     * Returns a segment by ID.
     */
    public function getSegmentById(int $segmentId): ?SegmentElement
    {
        return Campaign::$plugin->segments->getSegmentById($segmentId);
    }

    /**
     * Returns a sendout by ID.
     */
    public function getSendoutById(int $sendoutId): ?SendoutElement
    {
        return Campaign::$plugin->sendouts->getSendoutById($sendoutId);
    }

    /**
     * Returns all sendout types.
     */
    public function getAllSendoutTypes(): array
    {
        return SendoutElement::sendoutTypes();
    }

    /**
     * Returns all imports.
     */
    public function getAllImports(): array
    {
        return Campaign::$plugin->imports->getAllImports();
    }

    /**
     * Returns an import by ID.
     */
    public function getImportById(int $importId): ?ImportModel
    {
        return Campaign::$plugin->imports->getImportById($importId);
    }

    /**
     * Returns reCAPTCHA markup.
     */
    public function getRecaptcha(): Markup
    {
        $output = '';
        $settings = Campaign::$plugin->settings;

        if ($settings->reCaptcha) {
            $id = 'campaign-recaptcha-' . StringHelper::randomString(6);
            $reCaptchaSiteKey = $settings->getRecaptchaSiteKey();
            $action = RecaptchaHelper::RECAPTCHA_ACTION;

            $output = <<<HTML
                <input id="$id" type="hidden" name="g-recaptcha-response" value="">
                <script src="https://www.google.com/recaptcha/api.js?render=$reCaptchaSiteKey"></script>
                <script>
                    grecaptcha.ready(function() {
                        grecaptcha.execute('$reCaptchaSiteKey', {
                            action: '$action',
                        }).then(function(token) {
                            document.getElementById('$id').value = token;
                        });
                    });
                </script>
            HTML;
        }

        return Template::raw($output);
    }

    /**
     * Returns Turnstile markup.
     */
    public function getTurnstile(): Markup
    {
        $output = '';
        $settings = Campaign::$plugin->settings;

        if ($settings->turnstile) {
            $id = 'campaign-turnstile-' . StringHelper::randomString(6);
            $turnstileSiteKey = $settings->getTurnstileSiteKey();
            $action = TurnstileHelper::TURNSTILE_ACTION;

            $output = <<<HTML
                <input id="$id" type="hidden" name="cf-turnstile-response" value="">
                <script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit"></script>
                <script>
                    turnstile.ready(function () {
                        turnstile.render('#$id', {
                            sitekey: '$turnstileSiteKey',
                            action: '$action',
                        });
                    });
                </script>
            HTML;
        }

        return Template::raw($output);
    }

    /**
     * Returns plugin settings.
     */
    public function getSettings(): SettingsModel
    {
        return Campaign::$plugin->settings;
    }

    /**
     * Returns whether the current user can edit contacts.
     */
    public function canUserEditContacts(): bool
    {
        return Campaign::$plugin->canUserEditContacts();
    }
}
