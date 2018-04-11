<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign;

use craft\helpers\ConfigHelper;
use putyourlightson\campaign\controllers\TrackerController;
use putyourlightson\campaign\models\SettingsModel;
use putyourlightson\campaign\services\CampaignsService;
use putyourlightson\campaign\services\CampaignTypesService;
use putyourlightson\campaign\services\ContactsService;
use putyourlightson\campaign\services\ExportsService;
use putyourlightson\campaign\services\ImportsService;
use putyourlightson\campaign\services\MailingListsService;
use putyourlightson\campaign\services\MailingListTypesService;
use putyourlightson\campaign\services\ReportsService;
use putyourlightson\campaign\services\SegmentsService;
use putyourlightson\campaign\services\SendoutsService;
use putyourlightson\campaign\services\SettingsService;
use putyourlightson\campaign\services\TrackerService;
use putyourlightson\campaign\services\WebhookService;
use putyourlightson\campaign\twigextensions\CampaignTwigExtension;
use putyourlightson\campaign\variables\CampaignVariable;

use Craft;
use craft\base\Plugin;
use craft\errors\MissingComponentException;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\helpers\UrlHelper;
use craft\helpers\StringHelper;
use craft\helpers\MailerHelper;
use craft\mail\Mailer;
use craft\mail\Message;
use craft\mail\transportadapters\Sendmail;
use craft\services\UserPermissions;
use craft\web\UrlManager;
use craft\web\User;
use craft\web\twig\variables\CraftVariable;
use yii\base\Event;
use yii\web\ForbiddenHttpException;

/**
 * Campaign plugin
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 *
 * @property  CampaignsService $campaigns
 * @property  CampaignTypesService $campaignTypes
 * @property  ContactsService $contacts
 * @property  ExportsService $exports
 * @property  ImportsService $imports
 * @property  MailingListsService $mailingLists
 * @property  MailingListTypesService $mailingListTypes
 * @property  ReportsService $reports
 * @property  SegmentsService $segments
 * @property  SendoutsService $sendouts
 * @property  SettingsService $settings
 * @property  TrackerService $tracker
 * @property  WebhookService $webhook
 *
 * @property  array|null $cpNavItem
 * @property  mixed $settingsResponse
 */
class Campaign extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var Campaign
     */
    public static $plugin;

    // Public Methods
    // =========================================================================

    public function init()
    {
        parent::init();
        self::$plugin = $this;

        // Add tracker controller shorthand to controller map
        $this->controllerMap = ['t' => TrackerController::class];

        // Register services as components
        $this->setComponents([
            'campaigns' => CampaignsService::class,
            'campaignTypes' => CampaignTypesService::class,
            'contacts' => ContactsService::class,
            'exports' => ExportsService::class,
            'imports' => ImportsService::class,
            'mailingLists' => MailingListsService::class,
            'mailingListTypes' => MailingListTypesService::class,
            'reports' => ReportsService::class,
            'segments' => SegmentsService::class,
            'sendouts' => SendoutsService::class,
            'settings' => SettingsService::class,
            'tracker' => TrackerService::class,
            'webhook' => WebhookService::class,
        ]);

        // Register Twig extension
        Craft::$app->view->registerTwigExtension(new CampaignTwigExtension());

        // Register variable
        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function(Event $event) {
            /** @var CraftVariable $variable */
            $variable = $event->sender;
            $variable->set('campaign', CampaignVariable::class);
        });

        // Do some house-cleaning after login to CP
        if (Craft::$app->getRequest()->getIsCpRequest()) {
            Event::on(User::class, User::EVENT_AFTER_LOGIN, function() {
                $this->contacts->purgeExpiredPendingContacts();
                $this->sendouts->queuePendingSendouts();
            });
        }

        // Register CP URL rules event
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules['campaign/reports/campaigns/<campaignId:\d+>'] = ['template' => 'campaign/reports/campaigns/_view'];              $event->rules['campaign/reports/campaigns/<campaignId:\d+>/contact-activity'] = ['template' => 'campaign/reports/campaigns/_contact-activity'];
            $event->rules['campaign/reports/campaigns/<campaignId:\d+>/links'] = ['template' => 'campaign/reports/campaigns/_links'];
            $event->rules['campaign/reports/campaigns/<campaignId:\d+>/locations'] = ['template' => 'campaign/reports/campaigns/_locations'];
            $event->rules['campaign/reports/campaigns/<campaignId:\d+>/devices'] = ['template' => 'campaign/reports/campaigns/_devices'];
            $event->rules['campaign/reports/contacts/<contactId:\d+>'] = ['template' => 'campaign/reports/contacts/_view'];
            $event->rules['campaign/reports/contacts/<contactId:\d+>/campaign-activity'] = ['template' => 'campaign/reports/contacts/_campaign-activity'];
            $event->rules['campaign/reports/contacts/<contactId:\d+>/mailinglist-activity'] = ['template' => 'campaign/reports/contacts/_mailinglist-activity'];
            $event->rules['campaign/reports/mailinglists/<mailingListId:\d+>'] = ['template' => 'campaign/reports/mailinglists/_view'];
            $event->rules['campaign/reports/mailinglists/<mailingListId:\d+>/contact-activity'] = ['template' => 'campaign/reports/mailinglists/_contact-activity'];
            $event->rules['campaign/reports/mailinglists/<mailingListId:\d+>/locations'] = ['template' => 'campaign/reports/mailinglists/_locations'];
            $event->rules['campaign/reports/mailinglists/<mailingListId:\d+>/devices'] = ['template' => 'campaign/reports/mailinglists/_devices'];
            $event->rules['campaign/campaigns/<campaignTypeHandle:{handle}>'] = ['template' => 'campaign/campaigns/index'];
            $event->rules['campaign/campaigns/<campaignTypeHandle:{handle}>/new'] = 'campaign/campaigns/edit-campaign';
            $event->rules['campaign/campaigns/<campaignTypeHandle:{handle}>/<campaignId:\d+>'] = 'campaign/campaigns/edit-campaign';
            $event->rules['campaign/contacts/new'] = 'campaign/contacts/edit-contact';
            $event->rules['campaign/contacts/<contactId:\d+>'] = 'campaign/contacts/edit-contact';
            $event->rules['campaign/mailinglists/<mailingListTypeHandle:{handle}>'] = ['template' => 'campaign/mailinglists/index'];
            $event->rules['campaign/mailinglists/<mailingListTypeHandle:{handle}>/new'] = 'campaign/mailing-lists/edit-mailing-list';
            $event->rules['campaign/mailinglists/<mailingListTypeHandle:{handle}>/<mailingListId:\d+>'] = 'campaign/mailing-lists/edit-mailing-list';
            $event->rules['campaign/segments/new'] = 'campaign/segments/edit-segment';
            $event->rules['campaign/segments/<segmentId:\d+>'] = 'campaign/segments/edit-segment';
            $event->rules['campaign/sendouts/<sendoutType:{handle}>'] = ['template' => 'campaign/sendouts/index'];
            $event->rules['campaign/sendouts/<sendoutType:{handle}>/new'] = 'campaign/sendouts/edit-sendout';
            $event->rules['campaign/sendouts/<sendoutType:{handle}>/<sendoutId:\d+>'] = 'campaign/sendouts/edit-sendout';
            $event->rules['campaign/import-export/import/<importId:\d+>'] = ['template' => 'campaign/import-export/import/_view'];
            $event->rules['campaign/import-export/export'] = 'campaign/exports/new-export';
            $event->rules['campaign/settings/general'] = 'campaign/settings/edit-general';
            $event->rules['campaign/settings/email'] = 'campaign/settings/edit-email';
            $event->rules['campaign/settings/contact'] = 'campaign/settings/edit-contact';
            $event->rules['campaign/settings/campaigntypes/new'] = 'campaign/campaign-types/edit-campaign-type';
            $event->rules['campaign/settings/campaigntypes/<campaignTypeId:\d+>'] = 'campaign/campaign-types/edit-campaign-type';
            $event->rules['campaign/settings/mailinglisttypes/new'] = 'campaign/mailing-list-types/edit-mailing-list-type';
            $event->rules['campaign/settings/mailinglisttypes/<mailingListTypeId:\d+>'] = 'campaign/mailing-list-types/edit-mailing-list-type';
        });

        // Register user permissions if edition is pro
        if (Craft::$app->getEdition() === Craft::Pro) {
            Event::on(UserPermissions::class, UserPermissions::EVENT_REGISTER_PERMISSIONS, function(RegisterUserPermissionsEvent $event) {
                $event->permissions['Campaign']['campaign-reports'] = ['label' => Craft::t('campaign', 'Manage reports')];
                $event->permissions['Campaign']['campaign-campaigns'] = ['label' => Craft::t('campaign', 'Manage campaigns')];
                $event->permissions['Campaign']['campaign-contacts'] = ['label' => Craft::t('campaign', 'Manage contacts')];
                $event->permissions['Campaign']['campaign-mailingLists'] = ['label' => Craft::t('campaign', 'Manage mailing lists')];

                if ($this->isPro()) {
                    $event->permissions['Campaign']['campaign-segments'] = ['label' => Craft::t('campaign', 'Manage segments')];
                }

                $event->permissions['Campaign']['campaign-sendouts'] = [
                        'label' => Craft::t('campaign', 'Manage sendouts'),
                        'nested' => ['campaign-sendSendouts' => ['label' => Craft::t('campaign', 'Send sendouts')]],
                ];
                $event->permissions['Campaign']['campaign-import'] = ['label' => Craft::t('campaign', 'Import contacts')];
                $event->permissions['Campaign']['campaign-export'] = ['label' => Craft::t('campaign', 'Export contacts')];
                $event->permissions['Campaign']['campaign-settings'] = ['label' => Craft::t('campaign', 'Manage plugin settings')];
            });
        }
    }

    /**
     * @inheritdoc
     */
    public function getSettings()
    {
        /** @var SettingsModel $settings */
        $settings = parent::getSettings();

        // Normalize time duration settings
        $settings->purgePendingContactsDuration = ConfigHelper::durationInSeconds($settings->purgePendingContactsDuration);

        return $settings;
    }

    /**
     * @inheritdoc
     */
    public function getCpNavItem()
    {
        $user = Craft::$app->getUser();

        $cpNavItem =  parent::getCpNavItem();
        $cpNavItem['subnav'] = [];

        // Show nav items based on permissions
        if ($user->checkPermission('campaign-reports')) {
            $cpNavItem['subnav']['reports'] = ['label' => Craft::t('campaign', 'Reports'), 'url' => 'campaign/reports'];
        }
        if ($user->checkPermission('campaign-campaigns')) {
            $cpNavItem['subnav']['campaigns'] = ['label' => Craft::t('campaign', 'Campaigns'), 'url' => 'campaign/campaigns'];
        }
        if ($user->checkPermission('campaign-contacts')) {
            $cpNavItem['subnav']['contacts'] = ['label' => Craft::t('campaign', 'Contacts'), 'url' => 'campaign/contacts'];
        }
        if ($user->checkPermission('campaign-mailingLists')) {
            $cpNavItem['subnav']['mailinglists'] = ['label' => Craft::t('campaign', 'Mailing Lists'), 'url' => 'campaign/mailinglists'];
        }
        if ($user->checkPermission('campaign-segments') AND $this->isPro()) {
            $cpNavItem['subnav']['segments'] = ['label' => Craft::t('campaign', 'Segments'), 'url' => 'campaign/segments'];
        }
        if ($user->checkPermission('campaign-sendouts')) {
            $cpNavItem['subnav']['sendouts'] = ['label' => Craft::t('campaign', 'Sendouts'), 'url' => 'campaign/sendouts'];
        }
        if ($user->checkPermission('campaign-import') OR $user->checkPermission('campaign-export')) {
            $cpNavItem['subnav']['import-export'] = ['label' => Craft::t('campaign', 'Import/Export'), 'url' => 'campaign/import-export'];
        }
        if ($user->checkPermission('campaign-settings')) {
            $cpNavItem['subnav']['settings'] = ['label' => Craft::t('campaign', 'Settings'), 'url' => 'campaign/settings'];
        }

        return $cpNavItem;
    }

    /**
     * @inheritdoc
     */
    public function getSettingsResponse()
    {
        $url = UrlHelper::cpUrl('campaign/settings');

        return Craft::$app->getResponse()->redirect($url);
    }

    /**
     * Returns true if pro version
     *
     * @return bool
     */
    public function isPro(): bool
    {
        return Craft::$app->plugins->getPlugin('campaign-pro') !== null;
    }

    /**
     * Checks whether the plugin is the pro version
     *
     * @throws ForbiddenHttpException
     */
    public function requirePro()
    {
        if ($this->isPro() === false) {
            throw new ForbiddenHttpException(Craft::t('campaign', 'Campaign Pro is required to perform this action'));
        }
    }

    /**
     * Creates a mailer
     *
     * @param SettingsModel|null $settings
     *
     * @return Mailer
     * @throws MissingComponentException
     */
    public function createMailer($settings = null): Mailer
    {
        if ($settings == null) {
            $settings = self::$plugin->getSettings();
        }

        // Create the transport adapter
        $adapter = MailerHelper::createTransportAdapter($settings->transportType, $settings->transportSettings);

        // Create the mailer
        $mailer = new Mailer([
            'messageClass' => Message::class,
            'transport' => $adapter->defineTransport(),
        ]);

        return $mailer;
    }

    /**
     * Sets memory and time limits
     */
    public function maxPowerLieutenant()
    {
        $settings = self::$plugin->getSettings();

        // Set memory limit
        @ini_set('memory_limit', $settings->memoryLimit);

        // Set time limit
        @set_time_limit($settings->timeLimit);
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function createSettingsModel()
    {
        $settings = new SettingsModel();

        // Set defaults
        $settings->apiKey = StringHelper::randomString(16);
        $settings->defaultFromName = Craft::$app->getSystemSettings()->getEmailSettings()->fromName;
        $settings->defaultFromEmail = Craft::$app->getSystemSettings()->getEmailSettings()->fromEmail;
        $settings->transportType = Sendmail::class;

        return $settings;
    }

    /**
     * @inheritdoc
     */
    protected function afterInstall()
    {
        // Create and save default settings
        $settings = $this->createSettingsModel();
        self::$plugin->settings->saveSettings($settings);

        // Redirect to welcome page
        $url = UrlHelper::cpUrl('campaign/welcome');

        Craft::$app->getResponse()->redirect($url)->send();
    }
}
