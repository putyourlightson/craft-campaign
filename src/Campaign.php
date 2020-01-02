<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign;

use Craft;
use craft\base\Plugin;
use craft\elements\User;
use craft\errors\MissingComponentException;
use craft\events\PluginEvent;
use craft\events\RebuildConfigEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\helpers\App;
use craft\helpers\UrlHelper;
use craft\helpers\StringHelper;
use craft\helpers\MailerHelper;
use craft\mail\Mailer;
use craft\mail\Message;
use craft\mail\transportadapters\Sendmail;
use craft\services\Fields;
use craft\services\Plugins;
use craft\services\ProjectConfig;
use craft\services\Sites;
use craft\services\UserPermissions;
use craft\services\Utilities;
use craft\web\UrlManager;
use craft\web\twig\variables\CraftVariable;
use putyourlightson\campaign\controllers\TrackerController;
use putyourlightson\campaign\fields\CampaignsField;
use putyourlightson\campaign\fields\ContactsField;
use putyourlightson\campaign\fields\MailingListsField;
use putyourlightson\campaign\helpers\ProjectConfigDataHelper;
use putyourlightson\campaign\models\SettingsModel;
use putyourlightson\campaign\services\CampaignsService;
use putyourlightson\campaign\services\CampaignTypesService;
use putyourlightson\campaign\services\ContactsService;
use putyourlightson\campaign\services\ExportsService;
use putyourlightson\campaign\services\FormsService;
use putyourlightson\campaign\services\ImportsService;
use putyourlightson\campaign\services\MailingListsService;
use putyourlightson\campaign\services\MailingListTypesService;
use putyourlightson\campaign\services\PendingContactsService;
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
use putyourlightson\logtofile\LogToFile;
use yii\base\Event;
use yii\web\ForbiddenHttpException;

/**
 * Campaign plugin
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 *
 * @property CampaignsService $campaigns
 * @property CampaignTypesService $campaignTypes
 * @property ContactsService $contacts
 * @property ExportsService $exports
 * @property FormsService $forms
 * @property ImportsService $imports
 * @property MailingListsService $mailingLists
 * @property MailingListTypesService $mailingListTypes
 * @property PendingContactsService $pendingContacts
 * @property ReportsService $reports
 * @property SegmentsService $segments
 * @property SendoutsService $sendouts
 * @property SettingsService $settings
 * @property SyncService $sync
 * @property TrackerService $tracker
 * @property WebhookService $webhook
 * @property Mailer $mailer
 * @property array|null $cpNavItem
 * @property array $cpRoutes
 * @property array $cpPermissions
 * @property bool $isPro
 * @property mixed $settingsResponse
 *
 * @method SettingsModel getSettings()
 */
class Campaign extends Plugin
{
    // Constants
    // =========================================================================

    // Edition constants
    const EDITION_LITE = 'lite';
    const EDITION_PRO = 'pro';

    // Static
    // =========================================================================

    /**
     * @var Campaign
     */
    public static $plugin;

    /**
     * @inheritdoc
     */
    public static function editions(): array
    {
        return [
            self::EDITION_LITE,
            self::EDITION_PRO,
        ];
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        self::$plugin = $this;

        $this->_registerComponents();
        $this->_registerFieldTypes();
        $this->_registerAfterInstallEvent();
        $this->_registerProjectConfigListeners();

        // Register tracker controller shorthand for site requests
        if (Craft::$app->getRequest()->getIsSiteRequest()) {
            $this->controllerMap = ['t' => TrackerController::class];
        }

        // Register Twig extension
        Craft::$app->view->registerTwigExtension(new CampaignTwigExtension());

        // Register variable
        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT,
            function(Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('campaign', CampaignVariable::class);
            }
        );

        // Register CP URL rules event
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function(RegisterUrlRulesEvent $event) {
                $event->rules = array_merge($event->rules, $this->getCpRoutes());
            }
        );

        // Register utility
        Event::on(Utilities::class, Utilities::EVENT_REGISTER_UTILITY_TYPES,
            function(RegisterComponentTypesEvent $event) {
                if (Craft::$app->getUser()->checkPermission('campaign:utility')) {
                    $event->types[] = CampaignUtility::class;
                }
            }
        );

        // If Craft edition is pro
        if (Craft::$app->getEdition() === Craft::Pro) {
            // Register user permissions
            Event::on(UserPermissions::class, UserPermissions::EVENT_REGISTER_PERMISSIONS,
                function(RegisterUserPermissionsEvent $event) {
                    $event->permissions['Campaign'] = $this->getCpPermissions();
                }
            );

            $this->sync->registerUserEvents();
        }
    }

    /**
     * @inheritdoc
     */
    public function getCpNavItem()
    {
        $cpNavItem = parent::getCpNavItem();
        $cpNavItem['subnav'] = [];

        /** @var User|null $currentUser */
        $currentUser = Craft::$app->getUser()->getIdentity();

        if ($currentUser === null) {
            return $cpNavItem;
        }

        $allowAdminChanges = Craft::$app->getConfig()->getGeneral()->allowAdminChanges;

        // Show nav items based on permissions
        if ($currentUser->can('campaign:campaigns')) {
            $cpNavItem['subnav']['campaigns'] = ['label' => Craft::t('campaign', 'Campaigns'), 'url' => 'campaign/campaigns'];
        }
        if ($currentUser->can('campaign:contacts')) {
            $cpNavItem['subnav']['contacts'] = ['label' => Craft::t('campaign', 'Contacts'), 'url' => 'campaign/contacts'];
        }
        if ($currentUser->can('campaign:mailingLists')) {
            $cpNavItem['subnav']['mailinglists'] = ['label' => Craft::t('campaign', 'Mailing Lists'), 'url' => 'campaign/mailinglists'];
        }
        if ($currentUser->can('campaign:segments') && $this->getIsPro()) {
            $cpNavItem['subnav']['segments'] = ['label' => Craft::t('campaign', 'Segments'), 'url' => 'campaign/segments'];
        }
        if ($currentUser->can('campaign:sendouts')) {
            $cpNavItem['subnav']['sendouts'] = ['label' => Craft::t('campaign', 'Sendouts'), 'url' => 'campaign/sendouts'];
        }
        if ($currentUser->can('campaign:reports')) {
            $cpNavItem['subnav']['reports'] = ['label' => Craft::t('campaign', 'Reports'), 'url' => 'campaign/reports'];
        }
        if ($allowAdminChanges && $currentUser->can('campaign:settings')) {
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
        return $this->is(Campaign::EDITION_PRO);
    }

    /**
     * Throws an exception if the plugin edition is not pro
     *
     * @throws ForbiddenHttpException
     */
    public function requirePro()
    {
        if (!$this->getIsPro()) {
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
     * Logs an action
     *
     * @param string $message
     * @param array $params
     */
    public function log(string $message, array $params = [])
    {
        $user = Craft::$app->getUser()->getIdentity();

        if ($user !== null) {
            $params['username'] = $user->username;
        }

        $message = Craft::t('campaign', $message, $params);

        LogToFile::info($message, 'campaign');
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function createSettingsModel(): SettingsModel
    {
        $settings = new SettingsModel();
        $mailSettings = App::mailSettings();

        // Set defaults
        $settings->apiKey = StringHelper::randomString(16);
        $settings->fromNamesEmails = [[
            $mailSettings->fromName,
            $mailSettings->fromEmail,
            '',
            Craft::$app->getSites()->getPrimarySite()->id,
        ]];
        $settings->transportType = Sendmail::class;

        return $settings;
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
            'campaign/contacts/import/<importId:\d+>' => ['template' => 'campaign/contacts/import/_view'],
            'campaign/contacts/import' => 'campaign/imports/index',
            'campaign/contacts/import/<siteHandle:{handle}>' => 'campaign/imports/index',
            'campaign/contacts/export' => 'campaign/exports/index',
            'campaign/contacts/export/<siteHandle:{handle}>' => 'campaign/exports/index',
            'campaign/contacts/sync' => 'campaign/sync/index',
            'campaign/contacts/sync/<siteHandle:{handle}>' => 'campaign/sync/index',
            'campaign/mailinglists/<mailingListTypeHandle:{handle}>' => ['template' => 'campaign/mailinglists/index'],
            'campaign/mailinglists/<mailingListTypeHandle:{handle}>/new' => 'campaign/mailing-lists/edit-mailing-list',
            'campaign/mailinglists/<mailingListTypeHandle:{handle}>/<mailingListId:\d+>' => 'campaign/mailing-lists/edit-mailing-list',
            'campaign/segments/<segmentType:{handle}>' => ['template' => 'campaign/segments/index'],
            'campaign/segments/<segmentType:{handle}>/new' => 'campaign/segments/edit-segment',
            'campaign/segments/<segmentType:{handle}>/new/<siteHandle:{handle}>' => 'campaign/segments/edit-segment',
            'campaign/segments/<segmentType:{handle}>/<segmentId:\d+>' => 'campaign/segments/edit-segment',
            'campaign/sendouts/<sendoutType:{handle}>' => ['template' => 'campaign/sendouts/index'],
            'campaign/sendouts/<sendoutType:{handle}>/new' => 'campaign/sendouts/edit-sendout',
            'campaign/sendouts/<sendoutType:{handle}>/new/<siteHandle:{handle}>' => 'campaign/sendouts/edit-sendout',
            'campaign/sendouts/<sendoutType:{handle}>/<sendoutId:\d+>' => 'campaign/sendouts/edit-sendout',
            'campaign/sendouts/<sendoutType:{handle}>/<sendoutId:\d+>/<siteHandle:{handle}>' => 'campaign/sendouts/edit-sendout',
            'campaign/settings/general' => 'campaign/settings/edit-general',
            'campaign/settings/email' => 'campaign/settings/edit-email',
            'campaign/settings/sendout' => 'campaign/settings/edit-sendout',
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

    // Private Methods
    // =========================================================================

    /**
     * Registers components.
     */
    private function _registerComponents()
    {
        // Register services as components
        $this->setComponents([
            'campaigns' => CampaignsService::class,
            'campaignTypes' => CampaignTypesService::class,
            'contacts' => ContactsService::class,
            'exports' => ExportsService::class,
            'forms' => FormsService::class,
            'imports' => ImportsService::class,
            'mailingLists' => MailingListsService::class,
            'mailingListTypes' => MailingListTypesService::class,
            'pendingContacts' => PendingContactsService::class,
            'reports' => ReportsService::class,
            'segments' => SegmentsService::class,
            'sendouts' => SendoutsService::class,
            'settings' => SettingsService::class,
            'sync' => SyncService::class,
            'tracker' => TrackerService::class,
            'webhook' => WebhookService::class,
        ]);

        // Register mailer component
        $this->set('mailer', function() {
            return $this->createMailer();
        });
    }

    /**
     * Registers custom field types.
     */
    private function _registerFieldTypes()
    {
        Event::on(Fields::class, Fields::EVENT_REGISTER_FIELD_TYPES, function(RegisterComponentTypesEvent $event) {
            $event->types[] = CampaignsField::class;
            $event->types[] = ContactsField::class;
            $event->types[] = MailingListsField::class;
        });
    }

    /**
     * Registers after install event.
     */
    private function _registerAfterInstallEvent()
    {
        Event::on(Plugins::class, Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function(PluginEvent $event) {
                if ($event->plugin === $this) {
                    // Create and save default settings
                    $settings = $this->createSettingsModel();
                    $this->settings->saveSettings($settings);

                    if (Craft::$app->getRequest()->getIsCpRequest()) {
                        // Redirect to welcome page
                        Craft::$app->getResponse()->redirect(
                            UrlHelper::cpUrl('campaign/welcome')
                        )->send();
                    }
                }
            }
        );
    }

    /**
     * Registers event listeners for project config changes.
     */
    private function _registerProjectConfigListeners()
    {
        // Campaign types
        Craft::$app->projectConfig
            ->onAdd(CampaignTypesService::CONFIG_CAMPAIGNTYPES_KEY.'.{uid}', [$this->campaignTypes, 'handleChangedCampaignType'])
            ->onUpdate(CampaignTypesService::CONFIG_CAMPAIGNTYPES_KEY.'.{uid}', [$this->campaignTypes, 'handleChangedCampaignType'])
            ->onRemove(CampaignTypesService::CONFIG_CAMPAIGNTYPES_KEY.'.{uid}', [$this->campaignTypes, 'handleDeletedCampaignType']);
        Event::on(Sites::class, Sites::EVENT_AFTER_DELETE_SITE, [$this->campaignTypes, 'handleDeletedSite']);
        Event::on(Fields::class, Fields::EVENT_AFTER_DELETE_FIELD, [$this->campaignTypes, 'pruneDeletedField']);

        // Mailing list types types
        Craft::$app->projectConfig
            ->onAdd(MailingListTypesService::CONFIG_MAILINGLISTTYPES_KEY.'.{uid}', [$this->mailingListTypes, 'handleChangedMailingListType'])
            ->onUpdate(MailingListTypesService::CONFIG_MAILINGLISTTYPES_KEY.'.{uid}', [$this->mailingListTypes, 'handleChangedMailingListType'])
            ->onRemove(MailingListTypesService::CONFIG_MAILINGLISTTYPES_KEY.'.{uid}', [$this->mailingListTypes, 'handleDeletedMailingListType']);
        Event::on(Sites::class, Sites::EVENT_AFTER_DELETE_SITE, [$this->mailingListTypes, 'handleDeletedSite']);
        Event::on(Fields::class, Fields::EVENT_AFTER_DELETE_FIELD, [$this->mailingListTypes, 'pruneDeletedField']);

        // Rebuild project config data
        Event::on(ProjectConfig::class, ProjectConfig::EVENT_REBUILD, function(RebuildConfigEvent $e) {
            $e->config['campaign'] = ProjectConfigDataHelper::rebuildProjectConfig();
        });
    }
}
