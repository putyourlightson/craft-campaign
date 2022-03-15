<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign;

use Craft;
use craft\base\Plugin;
use craft\controllers\LivePreviewController;
use craft\elements\User;
use craft\events\DefineFieldLayoutFieldsEvent;
use craft\events\FieldEvent;
use craft\events\PluginEvent;
use craft\events\RebuildConfigEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\fieldlayoutelements\TitleField;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\MailerHelper;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\mail\Mailer;
use craft\mail\Message;
use craft\mail\transportadapters\Sendmail;
use craft\models\FieldLayout;
use craft\services\Elements;
use craft\services\Fields;
use craft\services\Plugins;
use craft\services\ProjectConfig;
use craft\services\Sites;
use craft\services\UserPermissions;
use craft\services\Utilities;
use craft\web\Response;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use putyourlightson\campaign\assets\CpAsset;
use putyourlightson\campaign\controllers\TrackerController;
use putyourlightson\campaign\elements\CampaignElement;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\elements\SegmentElement;
use putyourlightson\campaign\elements\SendoutElement;
use putyourlightson\campaign\fieldlayoutelements\contacts\ContactEmailField;
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
use yii\base\ActionEvent;
use yii\base\Controller;
use yii\base\Event;
use yii\web\ForbiddenHttpException;

/**
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
    /**
     * @const string
     */
    public const EDITION_LITE = 'lite';

    /**
     * @const string
     */
    public const EDITION_PRO = 'pro';

    /**
     * @var Campaign
     */
    public static Campaign $plugin;

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

    /**
     * @inheritdoc
     */
    public bool $hasCpSettings = true;

    /**
     * @inheritdoc
     */
    public bool $hasCpSection = true;

    /**
     * @inheritdoc
     */
    public string $schemaVersion = '2.0.0-beta.1';

    /**
     * @inheritdoc
     */
    public string $minVersionRequired = '1.21.0';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;
        $this->name = Craft::t('campaign', 'Plugin Name');

        $this->_registerComponents();
        $this->_registerElementTypes();
        $this->_registerFieldTypes();
        $this->_registerAfterInstallEvent();
        $this->_registerFieldEvents();
        $this->_registerProjectConfigListeners();
        $this->_registerTemplateHooks();
        $this->_registerAllowedOrigins();
        $this->_registerTwigExtensions();
        $this->_registerVariables();

        // Register tracker controller shorthand for site requests
        if (Craft::$app->getRequest()->getIsSiteRequest()) {
            $this->controllerMap = ['t' => TrackerController::class];
        }

        if (Craft::$app->getRequest()->getIsCpRequest()) {
            $this->_registerNativeFields();
            $this->_registerAssetBundles();
            $this->_registerCpUrlRules();
            $this->_registerUtilities();
        }

        // If Craft edition is pro
        if (Craft::$app->getEdition() === Craft::Pro) {
            $this->_registerUserPermissions();
            $this->sync->registerUserEvents();
        }
    }

    /**
     * @inheritdoc
     */
    public function getCpNavItem(): ?array
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
    public function getSettingsResponse(): Response
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
        return true;//$this->is(self::EDITION_PRO);
    }

    /**
     * Throws an exception if the plugin edition is not pro
     *
     * @throws ForbiddenHttpException
     */
    public function requirePro()
    {
        if (!$this->getIsPro()) {
            throw new ForbiddenHttpException(Craft::t('campaign', 'Campaign Pro is required to perform this action.'));
        }
    }

    /**
     * Creates a mailer.
     */
    public function createMailer(SettingsModel $settings = null): Mailer
    {
        if ($settings == null) {
            $settings = $this->getSettings();
        }

        // Create the transport adapter
        $adapter = MailerHelper::createTransportAdapter($settings->transportType, $settings->transportSettings);

        // Create the mailer
        return new Mailer([
            'messageClass' => Message::class,
            'transport' => $adapter->defineTransport(),
        ]);
    }

    /**
     * Sets max memory and time limits.
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
     * Logs an action.
     */
    public function log(string $message, array $params = [])
    {
        /** @var User|null $user */
        $user = Craft::$app->getUser()->getIdentity();

        if ($user !== null) {
            $params['username'] = $user->username;
        }

        $message = Craft::t('campaign', $message, $params);

        LogToFile::info($message, 'campaign');
    }

    /**
     * @inheritdoc
     */
    protected function createSettingsModel(): SettingsModel
    {
        $settings = new SettingsModel();
        $mailSettings = App::mailSettings();

        // Set defaults
        $settings->apiKey = StringHelper::randomString(16);
        $settings->fromNamesEmails = [
            [
                App::parseEnv($mailSettings->fromName),
                App::parseEnv($mailSettings->fromEmail),
                '',
                Craft::$app->getSites()->getPrimarySite()->id,
            ],
        ];
        $settings->transportType = Sendmail::class;

        return $settings;
    }

    /**
     * Returns the CP routes.
     */
    protected function getCpRoutes(): array
    {
        return [
            'campaign/reports/campaigns/<campaignId:\d+>' => ['template' => 'campaign/reports/campaigns/_view'],
            'campaign/reports/campaigns/<campaignId:\d+>/recipients' => ['template' => 'campaign/reports/campaigns/_recipients'],
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
            'campaign/campaigns/<campaignTypeHandle:{handle}>/new' => 'campaign/campaigns/create',
            'campaign/campaigns/<campaignTypeHandle:{handle}>/<campaignId:\d+><slug:(?:-[^\/]*)?>' => 'campaign/campaigns/edit',
            'campaign/contacts/new' => 'campaign/contacts/create',
            'campaign/contacts/<contactId:\d+>' => 'campaign/contacts/edit',
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
            'campaign/mailinglists/<mailingListTypeHandle:{handle}>/new' => 'campaign/mailing-lists/create',
            'campaign/mailinglists/<mailingListTypeHandle:{handle}>/<mailingListId:\d+><slug:(?:-[^\/]*)?>' => 'campaign/mailing-lists/edit',
            'campaign/segments/<segmentType:{handle}>' => ['template' => 'campaign/segments/index'],
            'campaign/segments/<segmentType:{handle}>/new' => 'campaign/segments/create',
            'campaign/segments/<segmentType:{handle}>/new/<siteHandle:{handle}>' => 'campaign/segments/create',
            'campaign/segments/<segmentType:{handle}>/<segmentId:\d+><slug:(?:-[^\/]*)?>' => 'campaign/segments/edit',
            'campaign/sendouts/<sendoutType:{handle}>' => ['template' => 'campaign/sendouts/index'],
            'campaign/sendouts/<sendoutType:{handle}>/new' => 'campaign/sendouts/create',
            'campaign/sendouts/<sendoutType:{handle}>/new/<siteHandle:{handle}>' => 'campaign/sendouts/edit',
            'campaign/sendouts/<sendoutType:{handle}>/<sendoutId:\d+>' => 'campaign/sendouts/edit',
            'campaign/sendouts/<sendoutType:{handle}>/<sendoutId:\d+>/<siteHandle:{handle}>' => 'campaign/sendouts/edit',
            'campaign/settings/general' => 'campaign/settings/edit-general',
            'campaign/settings/email' => 'campaign/settings/edit-email',
            'campaign/settings/sendout' => 'campaign/settings/edit-sendout',
            'campaign/settings/contact' => 'campaign/settings/edit-contact',
            'campaign/settings/geoip' => 'campaign/settings/edit-geoip',
            'campaign/settings/recaptcha' => 'campaign/settings/edit-recaptcha',
            'campaign/settings/campaigntypes/new' => 'campaign/campaign-types/edit',
            'campaign/settings/campaigntypes/<campaignTypeId:\d+>' => 'campaign/campaign-types/edit',
            'campaign/settings/mailinglisttypes/new' => 'campaign/mailing-list-types/edit',
            'campaign/settings/mailinglisttypes/<mailingListTypeId:\d+>' => 'campaign/mailing-list-types/edit',
        ];
    }

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
     * Registers element types.
     */
    private function _registerElementTypes()
    {
        Event::on(Elements::class, Elements::EVENT_REGISTER_ELEMENT_TYPES,
            function(RegisterComponentTypesEvent $event) {
                $event->types[] = CampaignElement::class;
                $event->types[] = ContactElement::class;
                $event->types[] = MailingListElement::class;
                $event->types[] = SegmentElement::class;
                $event->types[] = SendoutElement::class;
            }
        );
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
                    // Don't proceed if plugin exists in incoming project config, otherwise updates won't be applied
                    // https://github.com/putyourlightson/craft-campaign/issues/191
                    if (Craft::$app->projectConfig->get('plugins.campaign', true) !== null) {
                        return;
                    }

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
     * Registers field events.
     */
    private function _registerFieldEvents()
    {
        Event::on(Fields::class, Fields::EVENT_AFTER_SAVE_FIELD,
            function(FieldEvent $event) {
                if ($event->isNew === false) {
                    $this->segments->updateField($event->field);
                }
            }
        );

        Event::on(Fields::class, Fields::EVENT_AFTER_DELETE_FIELD,
            function(FieldEvent $event) {
                $this->segments->deleteField($event->field);
            }
        );
    }

    /**
     * Registers event listeners for project config changes.
     */
    private function _registerProjectConfigListeners()
    {
        // Contact field layout
        Craft::$app->projectConfig
            ->onAdd(SettingsService::CONFIG_CONTACTFIELDLAYOUT_KEY, [$this->settings, 'handleChangedContactFieldLayout'])
            ->onUpdate(SettingsService::CONFIG_CONTACTFIELDLAYOUT_KEY, [$this->settings, 'handleChangedContactFieldLayout'])
            ->onRemove(SettingsService::CONFIG_CONTACTFIELDLAYOUT_KEY, [$this->settings, 'handleChangedContactFieldLayout']);
        Event::on(Fields::class, Fields::EVENT_AFTER_DELETE_FIELD, [$this->settings, 'pruneDeletedField']);

        // Campaign types
        Craft::$app->projectConfig
            ->onAdd(CampaignTypesService::CONFIG_CAMPAIGNTYPES_KEY . '.{uid}', [$this->campaignTypes, 'handleChangedCampaignType'])
            ->onUpdate(CampaignTypesService::CONFIG_CAMPAIGNTYPES_KEY . '.{uid}', [$this->campaignTypes, 'handleChangedCampaignType'])
            ->onRemove(CampaignTypesService::CONFIG_CAMPAIGNTYPES_KEY . '.{uid}', [$this->campaignTypes, 'handleDeletedCampaignType']);
        Event::on(Sites::class, Sites::EVENT_AFTER_DELETE_SITE, [$this->campaignTypes, 'handleDeletedSite']);
        Event::on(Fields::class, Fields::EVENT_AFTER_DELETE_FIELD, [$this->campaignTypes, 'pruneDeletedField']);

        // Mailing list types types
        Craft::$app->projectConfig
            ->onAdd(MailingListTypesService::CONFIG_MAILINGLISTTYPES_KEY . '.{uid}', [$this->mailingListTypes, 'handleChangedMailingListType'])
            ->onUpdate(MailingListTypesService::CONFIG_MAILINGLISTTYPES_KEY . '.{uid}', [$this->mailingListTypes, 'handleChangedMailingListType'])
            ->onRemove(MailingListTypesService::CONFIG_MAILINGLISTTYPES_KEY . '.{uid}', [$this->mailingListTypes, 'handleDeletedMailingListType']);
        Event::on(Sites::class, Sites::EVENT_AFTER_DELETE_SITE, [$this->mailingListTypes, 'handleDeletedSite']);
        Event::on(Fields::class, Fields::EVENT_AFTER_DELETE_FIELD, [$this->mailingListTypes, 'pruneDeletedField']);

        // Rebuild project config data
        Event::on(ProjectConfig::class, ProjectConfig::EVENT_REBUILD, function(RebuildConfigEvent $event) {
            $event->config['campaign'] = ProjectConfigDataHelper::rebuildProjectConfig();
        });
    }

    /**
     * Registers template hooks.
     *
     * @since 1.15.1
     */
    private function _registerTemplateHooks()
    {
        Craft::$app->getView()->hook('cp.users.edit.details', function($context) {
            /** @var User|null $user */
            $user = $context['user'] ?? null;

            if ($user === null || $user->email === null) {
                return '';
            }

            $contact = $this->contacts->getContactByEmail($user->email);

            if ($contact === null) {
                return '';
            }

            return Craft::$app->getView()->renderTemplate('campaign/_includes/user-contact', [
                'contact' => $contact,
            ]);
        });
    }

    /**
     * Registers allowed origins to make live preview work across multiple domains.
     * https://craftcms.com/knowledge-base/using-live-preview-across-multiple-subdomains
     * https://github.com/craftcms/cms/issues/7851
     *
     * @since 1.21.0
     */
    private function _registerAllowedOrigins()
    {
        Event::on(LivePreviewController::class, Controller::EVENT_BEFORE_ACTION,
            function(ActionEvent $event) {
                if ($event->action->id === 'preview') {
                    $origins = Craft::$app->getRequest()->getOrigin();

                    if (empty($origins)) {
                        return;
                    }

                    $allowedOrigins = Campaign::$plugin->getSettings()->allowedOrigins;

                    if (empty($allowedOrigins) || !is_array($allowedOrigins)) {
                        return;
                    }

                    // The origin can potentially return multiple comma-delimited values.
                    // https://github.com/craftcms/cms/issues/7851#issuecomment-904831170
                    /** @see GraphqlController::actionApi() */
                    $origins = ArrayHelper::filterEmptyStringsFromArray(array_map('trim', explode(',', $origins)));

                    foreach ($origins as $origin) {
                        if (in_array($origin, $allowedOrigins)) {
                            Craft::$app->getResponse()->getHeaders()->set('Access-Control-Allow-Origin', $origin);

                            return;
                        }
                    }
                }
            }
        );
    }

    /**
     * Registers Twig extensions.
     *
     * @since 2.0.0
     */
    private function _registerTwigExtensions()
    {
        Craft::$app->view->registerTwigExtension(new CampaignTwigExtension());
    }

    /**
     * Registers variables.
     *
     * @since 2.0.0
     */
    private function _registerVariables()
    {
        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT,
            function(Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('campaign', CampaignVariable::class);
            }
        );
    }

    /**
     * Registers native fields.
     *
     * @since 2.0.0
     */
    private function _registerNativeFields()
    {
        Event::on(FieldLayout::class, FieldLayout::EVENT_DEFINE_NATIVE_FIELDS,
            function(DefineFieldLayoutFieldsEvent $event) {
                /** @var FieldLayout $layout */
                $layout = $event->sender;

                if ($layout->type === CampaignElement::class || $layout->type === MailingListElement::class) {
                    $event->fields[] = TitleField::class;
                }

                if ($layout->type === ContactElement::class) {
                    $event->fields[] = ContactEmailField::class;
                }
            }
        );
    }

    /**
     * Registers asset bundles.
     *
     * @since 2.0.0
     */
    private function _registerAssetBundles()
    {
        Craft::$app->view->registerAssetBundle(CpAsset::class);
    }

    /**
     * Registers CP URL rules.
     *
     * @since 2.0.0
     */
    private function _registerCpUrlRules()
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function(RegisterUrlRulesEvent $event) {
                $event->rules = array_merge($event->rules, $this->getCpRoutes());
            }
        );
    }

    /**
     * Registers utilities.
     *
     * @since 2.0.0
     */
    private function _registerUtilities()
    {
        Event::on(Utilities::class, Utilities::EVENT_REGISTER_UTILITY_TYPES,
            function(RegisterComponentTypesEvent $event) {
                if (Craft::$app->getUser()->checkPermission('campaign:utility')) {
                    $event->types[] = CampaignUtility::class;
                }
            }
        );
    }

    /**
     * Registers user permissions.
     *
     * @since 2.0.0
     */
    private function _registerUserPermissions()
    {
        Event::on(UserPermissions::class, UserPermissions::EVENT_REGISTER_PERMISSIONS,
            function(RegisterUserPermissionsEvent $event) {
                $campaignTypePermissions = [];
                foreach ($this->campaignTypes->getAllCampaignTypes() as $campaignType) {
                    $campaignTypePermissions['campaign:campaigns:' . $campaignType->uid] = [
                        'label' => $campaignType->name,
                    ];
                }

                $mailingListTypePermissions = [];
                foreach ($this->mailingListTypes->getAllMailingListTypes() as $mailingListType) {
                    $mailingListTypePermissions['campaign:mailingLists:' . $mailingListType->uid] = [
                        'label' => $mailingListType->name,
                    ];
                }

                $permissions = [
                    'campaign:reports' => ['label' => Craft::t('campaign', 'Manage reports')],
                    'campaign:campaigns' => [
                        'label' => Craft::t('campaign', 'Manage campaigns'),
                        'nested' => $campaignTypePermissions,
                    ],
                    'campaign:contacts' => [
                        'label' => Craft::t('campaign', 'Manage contacts'),
                        'nested' => [
                            'campaign:importContacts' => ['label' => Craft::t('campaign', 'Import contacts')],
                            'campaign:exportContacts' => ['label' => Craft::t('campaign', 'Export contacts')],
                        ],
                    ],
                    'campaign:mailingLists' => [
                        'label' => Craft::t('campaign', 'Manage mailing lists'),
                        'nested' => $mailingListTypePermissions,
                    ],
                ];

                if ($this->getIsPro()) {
                    $permissions['campaign:contacts']['nested']['campaign:syncContacts'] = ['label' => Craft::t('campaign', 'Sync contacts')];
                    $permissions['campaign:segments'] = ['label' => Craft::t('campaign', 'Manage segments')];
                }

                $permissions['campaign:sendouts'] = [
                    'label' => Craft::t('campaign', 'Manage sendouts'),
                    'nested' => [
                        'campaign:sendSendouts' => ['label' => Craft::t('campaign', 'Send sendouts')],
                    ],
                ];
                $permissions['campaign:settings'] = ['label' => Craft::t('campaign', 'Manage plugin settings')];
                $permissions['campaign:utility'] = ['label' => Craft::t('campaign', 'Access utility')];

                $event->permissions[] = [
                    'heading' => 'Campaign',
                    'permissions' => $permissions,
                ];
            }
        );
    }
}
