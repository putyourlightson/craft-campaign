<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign;

use Craft;
use craft\base\Plugin;
use craft\errors\MissingComponentException;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\helpers\ConfigHelper;
use craft\helpers\UrlHelper;
use craft\helpers\StringHelper;
use craft\helpers\MailerHelper;
use craft\mail\Mailer;
use craft\mail\Message;
use craft\mail\transportadapters\Sendmail;
use craft\services\UserPermissions;
use craft\services\Utilities;
use craft\web\UrlManager;
use craft\web\twig\variables\CraftVariable;
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
use putyourlightson\campaign\services\SyncService;
use putyourlightson\campaign\services\TrackerService;
use putyourlightson\campaign\services\WebhookService;
use putyourlightson\campaign\twigextensions\CampaignTwigExtension;
use putyourlightson\campaign\utilities\CampaignUtility;
use putyourlightson\campaign\variables\CampaignVariable;
use yii\base\Event;
use yii\base\InvalidConfigException;
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
 * @property  SyncService $sync
 * @property  TrackerService $tracker
 * @property  WebhookService $webhook
 *
 * @property  array|null $cpNavItem
 * @property  array $cpRoutes
 * @property  array $cpPermissions
 * @property  bool $isPro
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
            'sync' => SyncService::class,
            'tracker' => TrackerService::class,
            'webhook' => WebhookService::class,
        ]);

        // Register tracker controller shorthand
        $this->controllerMap = ['t' => TrackerController::class];

        // Console request
        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            // Add console commands
            $this->controllerNamespace = __NAMESPACE__.'\console\controllers';
        }

        // Register Twig extension
        Craft::$app->view->registerTwigExtension(new CampaignTwigExtension());

        // Register variable
        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function(Event $event) {
            /** @var CraftVariable $variable */
            $variable = $event->sender;
            $variable->set('campaign', CampaignVariable::class);
        });

        // Register utility
        Event::on(Utilities::class, Utilities::EVENT_REGISTER_UTILITY_TYPES, function(RegisterComponentTypesEvent $event) {
            if (Craft::$app->getUser()->checkPermission('campaign:utility')) {
                $event->types[] = CampaignUtility::class;
            }
        });

        // Register CP URL rules event
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules = array_merge($event->rules, $this->getCpRoutes());
        });

        // If edition is pro
        if (Craft::$app->getEdition() === Craft::Pro) {
            // Register user permissions
            Event::on(UserPermissions::class, UserPermissions::EVENT_REGISTER_PERMISSIONS,
                function(RegisterUserPermissionsEvent $event) {
                    $event->permissions['Campaign'] = $this->getCpPermissions();
                }
            );

            // Register user events
            $this->sync->registerUserEvents();
        }
    }

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function getSettings(): SettingsModel
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
        if ($user->checkPermission('campaign:campaigns')) {
            $cpNavItem['subnav']['campaigns'] = ['label' => Craft::t('campaign', 'Campaigns'), 'url' => 'campaign/campaigns'];
        }
        if ($user->checkPermission('campaign:contacts')) {
            $cpNavItem['subnav']['contacts'] = ['label' => Craft::t('campaign', 'Contacts'), 'url' => 'campaign/contacts'];
        }
        if ($user->checkPermission('campaign:mailingLists')) {
            $cpNavItem['subnav']['mailinglists'] = ['label' => Craft::t('campaign', 'Mailing Lists'), 'url' => 'campaign/mailinglists'];
        }
        if ($user->checkPermission('campaign:segments') AND $this->getIsPro()) {
            $cpNavItem['subnav']['segments'] = ['label' => Craft::t('campaign', 'Segments'), 'url' => 'campaign/segments'];
        }
        if ($user->checkPermission('campaign:sendouts')) {
            $cpNavItem['subnav']['sendouts'] = ['label' => Craft::t('campaign', 'Sendouts'), 'url' => 'campaign/sendouts'];
        }
        if ($user->checkPermission('campaign:reports')) {
            $cpNavItem['subnav']['reports'] = ['label' => Craft::t('campaign', 'Reports'), 'url' => 'campaign/reports'];
        }
        if ($user->checkPermission('campaign:settings')) {
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
    public function getIsPro(): bool
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
        if ($this->getIsPro() === false) {
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
     * @throws InvalidConfigException
     */
    public function createMailer(SettingsModel $settings = null): Mailer
    {
        if ($settings == null) {
            $settings = $this->getSettings();
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
     *
     * @throws InvalidConfigException
     */
    public function maxPowerLieutenant()
    {
        $settings = $this->getSettings();

        // Set memory limit
        @ini_set('memory_limit', $settings->memoryLimit);

        // Set time limit
        @set_time_limit($settings->timeLimit);
    }

    /**
     * Logs a user action
     *
     * @param string $message
     * @param array $params
     * @param string|null $category
     */
    public function logUserAction(string $message, array $params, string $category = null)
    {
        $category = $category ?? 'Campaign';

        $params['username'] = Craft::$app->getUser()->getIdentity()->username;

        Craft::warning(Craft::t('campaign', $message, $params), $category);
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function createSettingsModel(): SettingsModel
    {
        $settings = new SettingsModel();
        $systemSettings = Craft::$app->getSystemSettings();

        // Set defaults
        $settings->apiKey = StringHelper::randomString(16);
        $settings->fromNamesEmails = [[
            $systemSettings->getEmailSettings()->fromName,
            $systemSettings->getEmailSettings()->fromEmail,
            Craft::$app->getSites()->getPrimarySite()->id,
        ]];
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
        $this->settings->saveSettings($settings);

        // Redirect to welcome page
        $url = UrlHelper::cpUrl('campaign/welcome');

        Craft::$app->getResponse()->redirect($url)->send();
    }

    /**
     * Returns the CP routes
     *
     * @return array
     */
    protected function getCpRoutes(): array
    {
        return [
            'campaign/reports/campaigns/<campaignId:\d+>' => ['template' => 'campaign/reports/campaigns/_view'],
            'campaign/reports/campaigns/<campaignId:\d+>/contact-activity' => ['template' => 'campaign/reports/campaigns/_contact-activity'],
            'campaign/reports/campaigns/<campaignId:\d+>/links' => ['template' => 'campaign/reports/campaigns/_links'],
            'campaign/reports/campaigns/<campaignId:\d+>/locations' => ['template' => 'campaign/reports/campaigns/_locations'],
            'campaign/reports/campaigns/<campaignId:\d+>/devices' => ['template' => 'campaign/reports/campaigns/_devices'],
            'campaign/reports/contacts/<contactId:\d+>' => ['template' => 'campaign/reports/contacts/_view'],
            'campaign/reports/contacts/<contactId:\d+>/campaign-activity' => ['template' => 'campaign/reports/contacts/_campaign-activity'],
            'campaign/reports/contacts/<contactId:\d+>/mailinglist-activity' => ['template' => 'campaign/reports/contacts/_mailinglist-activity'],
            'campaign/reports/mailinglists/<mailingListId:\d+>' => ['template' => 'campaign/reports/mailinglists/_view'],
            'campaign/reports/mailinglists/<mailingListId:\d+>/contact-activity' => ['template' => 'campaign/reports/mailinglists/_contact-activity'],
            'campaign/reports/mailinglists/<mailingListId:\d+>/locations' => ['template' => 'campaign/reports/mailinglists/_locations'],
            'campaign/reports/mailinglists/<mailingListId:\d+>/devices' => ['template' => 'campaign/reports/mailinglists/_devices'],
            'campaign/campaigns/<campaignTypeHandle:{handle}>' => ['template' => 'campaign/campaigns/index'],
            'campaign/campaigns/<campaignTypeHandle:{handle}>/new' => 'campaign/campaigns/edit-campaign',
            'campaign/campaigns/<campaignTypeHandle:{handle}>/<campaignId:\d+>' => 'campaign/campaigns/edit-campaign',
            'campaign/contacts/new' => 'campaign/contacts/edit-contact',
            'campaign/contacts/<contactId:\d+>' => 'campaign/contacts/edit-contact',
            'campaign/contacts/view' => 'campaign/contacts/index',
            'campaign/contacts/view/<siteHandle:{handle}>' => 'campaign/contacts/index',
            'campaign/contacts/import/<importId:\d+>' => ['template' => 'campaign/contacts/imports/_view'],
            'campaign/contacts/import' => 'campaign/imports/index',
            'campaign/contacts/import/<siteHandle:{handle}>' => 'campaign/imports/index',
            'campaign/contacts/export' => 'campaign/exports/index',
            'campaign/contacts/export/<siteHandle:{handle}>' => 'campaign/exports/index',
            'campaign/contacts/sync' => 'campaign/sync/index',
            'campaign/contacts/sync/<siteHandle:{handle}>' => 'campaign/sync/index',
            'campaign/mailinglists/<mailingListTypeHandle:{handle}>' => ['template' => 'campaign/mailinglists/index'],
            'campaign/mailinglists/<mailingListTypeHandle:{handle}>/new' => 'campaign/mailing-lists/edit-mailing-list',
            'campaign/mailinglists/<mailingListTypeHandle:{handle}>/<mailingListId:\d+>' => 'campaign/mailing-lists/edit-mailing-list',
            'campaign/segments/new' => 'campaign/segments/edit-segment',
            'campaign/segments/<segmentId:\d+>' => 'campaign/segments/edit-segment',
            'campaign/sendouts/<sendoutType:{handle}>' => ['template' => 'campaign/sendouts/index'],
            'campaign/sendouts/<sendoutType:{handle}>/new' => 'campaign/sendouts/edit-sendout',
            'campaign/sendouts/<sendoutType:{handle}>/new/<siteHandle:{handle}>' => 'campaign/sendouts/edit-sendout',
            'campaign/sendouts/<sendoutType:{handle}>/<sendoutId:\d+>' => 'campaign/sendouts/edit-sendout',
            'campaign/sendouts/<sendoutType:{handle}>/<sendoutId:\d+>/<siteHandle:{handle}>' => 'campaign/sendouts/edit-sendout',
            'campaign/settings/general' => 'campaign/settings/edit-general',
            'campaign/settings/email' => 'campaign/settings/edit-email',
            'campaign/settings/contact' => 'campaign/settings/edit-contact',
            'campaign/settings/geoip' => 'campaign/settings/edit-geoip',
            'campaign/settings/recaptcha' => 'campaign/settings/edit-recaptcha',
            'campaign/settings/campaigntypes/new' => 'campaign/campaign-types/edit-campaign-type',
            'campaign/settings/campaigntypes/<campaignTypeId:\d+>' => 'campaign/campaign-types/edit-campaign-type',
            'campaign/settings/mailinglisttypes/new' => 'campaign/mailing-list-types/edit-mailing-list-type',
            'campaign/settings/mailinglisttypes/<mailingListTypeId:\d+>' => 'campaign/mailing-list-types/edit-mailing-list-type',
        ];
    }

    /**
     * Returns the CP permissions
     *
     * @return array
     */
    protected function getCpPermissions(): array
    {
        $permissions = [
            'campaign:reports' => ['label' => Craft::t('campaign', 'Manage reports')],
            'campaign:campaigns' => ['label' => Craft::t('campaign', 'Manage campaigns')],
            'campaign:contacts' => [
                'label' => Craft::t('campaign', 'Manage contacts'),
                'nested' => [
                    'campaign:importContacts' => ['label' => Craft::t('campaign', 'Import contacts')],
                    'campaign:exportContacts' => ['label' => Craft::t('campaign', 'Export contacts')],
                ],
            ],
            'campaign:mailingLists' => ['label' => Craft::t('campaign', 'Manage mailing lists')],
        ];

        if ($this->getIsPro()) {
            $permissions['campaign:contacts']['nested']['campaign:syncContacts'] = ['label' => Craft::t('campaign', 'Sync contacts')];
            $permissions['campaign:segments'] = ['label' => Craft::t('campaign', 'Manage segments')];
        }

        $permissions['campaign:sendouts'] = [
            'label' => Craft::t('campaign', 'Manage sendouts'),
            'nested' => [
                'campaign:sendSendouts' => ['label' => Craft::t('campaign', 'Send sendouts')]
            ],
        ];

        $permissions['campaign:settings'] = ['label' => Craft::t('campaign', 'Manage plugin settings')];

        $permissions['campaign:utility'] = ['label' => Craft::t('campaign', 'Access utility')];

        return $permissions;
    }
}
