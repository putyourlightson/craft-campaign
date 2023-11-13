<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign;

use Craft;
use craft\base\Plugin;
use craft\controllers\PreviewController;
use craft\elements\User;
use craft\events\DefineFieldLayoutFieldsEvent;
use craft\events\FieldEvent;
use craft\events\PluginEvent;
use craft\events\RebuildConfigEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\feedme\events\RegisterFeedMeElementsEvent;
use craft\feedme\services\Elements as FeedMeElements;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\MailerHelper;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\log\MonologTarget;
use craft\mail\Mailer;
use craft\mail\Message;
use craft\mail\transportadapters\Sendmail;
use craft\models\FieldLayout;
use craft\services\Dashboard;
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
use Monolog\Formatter\LineFormatter;
use Psr\Log\LogLevel;
use putyourlightson\campaign\assets\CampaignAsset;
use putyourlightson\campaign\assets\CpAsset;
use putyourlightson\campaign\controllers\TrackerController;
use putyourlightson\campaign\elements\CampaignElement;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\elements\SegmentElement;
use putyourlightson\campaign\elements\SendoutElement;
use putyourlightson\campaign\fieldlayoutelements\campaigns\CampaignTitleField;
use putyourlightson\campaign\fieldlayoutelements\contacts\ContactEmailFieldLayoutElement;
use putyourlightson\campaign\fieldlayoutelements\NonTranslatableTitleField;
use putyourlightson\campaign\fields\CampaignsField;
use putyourlightson\campaign\fields\ContactsField;
use putyourlightson\campaign\fields\MailingListsField;
use putyourlightson\campaign\helpers\ProjectConfigDataHelper;
use putyourlightson\campaign\integrations\feedme\CampaignFeedMeElement;
use putyourlightson\campaign\integrations\feedme\ContactFeedMeElement;
use putyourlightson\campaign\integrations\feedme\MailingListFeedMeElement;
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
use putyourlightson\campaign\services\SyncService;
use putyourlightson\campaign\services\TrackerService;
use putyourlightson\campaign\services\WebhookService;
use putyourlightson\campaign\twigextensions\CampaignTwigExtension;
use putyourlightson\campaign\utilities\CampaignUtility;
use putyourlightson\campaign\variables\CampaignVariable;
use putyourlightson\campaign\widgets\CampaignStatsWidget;
use putyourlightson\campaign\widgets\MailingListStatsWidget;
use yii\base\ActionEvent;
use yii\base\Controller;
use yii\base\Event;
use yii\log\Logger;
use yii\web\ForbiddenHttpException;

/**
 * @property-read CampaignsService $campaigns
 * @property-read CampaignTypesService $campaignTypes
 * @property-read ContactsService $contacts
 * @property-read ExportsService $exports
 * @property-read FormsService $forms
 * @property-read ImportsService $imports
 * @property-read MailingListsService $mailingLists
 * @property-read MailingListTypesService $mailingListTypes
 * @property-read PendingContactsService $pendingContacts
 * @property-read ReportsService $reports
 * @property-read SegmentsService $segments
 * @property-read SendoutsService $sendouts
 * @property-read SyncService $sync
 * @property-read TrackerService $tracker
 * @property-read WebhookService $webhook
 * @property-read Mailer $mailer
 * @property-read array|null $cpNavItem
 * @property-read array $cpRoutes
 * @property-read bool $isPro
 * @property-read SettingsModel $settings
 * @property-read Response $settingsResponse
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
    public static function config(): array
    {
        return [
            'components' => [
                'campaigns' => ['class' => CampaignsService::class],
                'campaignTypes' => ['class' => CampaignTypesService::class],
                'contacts' => ['class' => ContactsService::class],
                'exports' => ['class' => ExportsService::class],
                'forms' => ['class' => FormsService::class],
                'imports' => ['class' => ImportsService::class],
                'mailingLists' => ['class' => MailingListsService::class],
                'mailingListTypes' => ['class' => MailingListTypesService::class],
                'pendingContacts' => ['class' => PendingContactsService::class],
                'reports' => ['class' => ReportsService::class],
                'segments' => ['class' => SegmentsService::class],
                'sendouts' => ['class' => SendoutsService::class],
                'sync' => ['class' => SyncService::class],
                'tracker' => ['class' => TrackerService::class],
                'webhook' => ['class' => WebhookService::class],
            ],
        ];
    }

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
    public string $schemaVersion = '2.7.0';

    /**
     * @inheritdoc
     */
    public string $minVersionRequired = '1.21.0';

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();
        self::$plugin = $this;
        $this->name = Craft::t('campaign', 'Plugin Name');

        $this->_registerComponents();
        $this->_registerVariables();
        $this->_registerLogTarget();
        $this->_registerElementTypes();
        $this->_registerFieldTypes();
        $this->_registerAfterInstallEvent();
        $this->_registerFieldEvents();
        $this->_registerProjectConfigListeners();
        $this->_registerTemplateHooks();
        $this->_registerAllowedOrigins();
        $this->_registerTwigExtensions();
        $this->_registerFeedMeElements();

        // Register tracker controller shorthand for site requests
        if (Craft::$app->getRequest()->getIsSiteRequest()) {
            $this->controllerMap = ['t' => TrackerController::class];
        }

        if (Craft::$app->getRequest()->getIsCpRequest()) {
            $this->_registerNativeFields();
            $this->_registerAssetBundles();
            $this->_registerCpUrlRules();
            $this->_registerUtilities();
            $this->_registerWidgets();
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
     * Returns true if pro version.
     */
    public function getIsPro(): bool
    {
        return $this->is(self::EDITION_PRO);
    }

    /**
     * Throws an exception if the plugin edition is not pro.
     */
    public function requirePro(): void
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
    public function maxPowerLieutenant(): void
    {
        $settings = $this->getSettings();

        // Set memory limit
        @ini_set('memory_limit', $settings->memoryLimit);

        // Try to reset time limit
        if (!function_exists('set_time_limit') || !@set_time_limit($settings->timeLimit)) {
            $this->log('set_time_limit() is not available');
        }
    }

    /**
     * Logs a message.
     */
    public function log(string $message, array $params = [], int $type = Logger::LEVEL_INFO): void
    {
        /** @var User|null $user */
        $user = Craft::$app->getUser()->getIdentity();

        if ($user !== null) {
            $params['username'] = $user->username;
        }

        $message = Craft::t('campaign', $message, $params);

        Craft::getLogger()->log($message, $type, 'campaign');
    }

    /**
     * Returns whether the current user can edit contacts.
     */
    public function userCanEditContacts(): bool
    {
        /** @var User|null $currentUser */
        $currentUser = Craft::$app->getUser()->getIdentity();

        if ($currentUser === null) {
            return false;
        }

        if (!$currentUser->can('campaign:contacts')) {
            return false;
        }

        if (Craft::$app->getIsMultiSite()) {
            // Edit permission for the primary site is required to edit contacts
            $primarySite = Craft::$app->getSites()->getPrimarySite();
            if (!$currentUser->can('editSite:' . $primarySite->uid)) {
                return false;
            }
        }

        return true;
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
            'campaign/campaigns/<campaignTypeHandle:{handle}>' => ['template' => 'campaign/campaigns/index'],
            'campaign/campaigns/<campaignTypeHandle:{handle}>/new' => 'campaign/campaigns/create',
            'campaign/campaigns/<campaignTypeHandle:{handle}>/<elementId:\d+><slug:(?:-[^\/]*)?>' => 'elements/edit',
            'campaign/contacts/new' => 'campaign/contacts/create',
            'campaign/contacts/<elementId:\d+>' => 'elements/edit',
            // TODO: remove in 5.0.0, when element index URLs include the source, added in Craft 4.3.0.
            'campaign/contacts/view/<sourceId:\d+>' => ['template' => 'campaign/contacts/view'],
            'campaign/contacts/import/<importId:\d+>' => ['template' => 'campaign/contacts/import/_view'],
            'campaign/contacts/import' => 'campaign/imports/index',
            'campaign/contacts/import/<siteHandle:{handle}>' => 'campaign/imports/index',
            'campaign/contacts/export' => 'campaign/exports/index',
            'campaign/contacts/export/<siteHandle:{handle}>' => 'campaign/exports/index',
            'campaign/contacts/sync' => 'campaign/sync/index',
            'campaign/contacts/sync/<siteHandle:{handle}>' => 'campaign/sync/index',
            'campaign/mailinglists/<mailingListTypeHandle:{handle}>' => ['template' => 'campaign/mailinglists/index'],
            'campaign/mailinglists/<mailingListTypeHandle:{handle}>/new' => 'campaign/mailing-lists/create',
            'campaign/mailinglists/<mailingListTypeHandle:{handle}>/<elementId:\d+><slug:(?:-[^\/]*)?>' => 'elements/edit',
            'campaign/segments/<segmentType:{handle}>' => ['template' => 'campaign/segments/index'],
            'campaign/segments/<segmentType:{handle}>/new' => 'campaign/segments/create',
            'campaign/segments/<segmentType:{handle}>/new/<siteHandle:{handle}>' => 'campaign/segments/create',
            'campaign/segments/<segmentType:{handle}>/<elementId:\d+><slug:(?:-[^\/]*)?>' => 'elements/edit',
            'campaign/sendouts/<sendoutType:{handle}>' => ['template' => 'campaign/sendouts/index'],
            'campaign/sendouts/<sendoutType:{handle}>/new' => 'campaign/sendouts/create',
            'campaign/sendouts/<sendoutType:{handle}>/new/<siteHandle:{handle}>' => 'campaign/sendouts/create',
            'campaign/sendouts/<sendoutType:{handle}>/<elementId:\d+>' => 'elements/edit',
            'campaign/sendouts/<sendoutType:{handle}>/preview/<sendoutId:\d+>' => 'campaign/sendouts/preview',
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
            'campaign/settings' => 'campaign/settings/index',
            'campaign/settings/general' => 'campaign/settings/edit-general',
            'campaign/settings/email' => 'campaign/settings/edit-email',
            'campaign/settings/sendout' => 'campaign/settings/edit-sendout',
            'campaign/settings/contact' => 'campaign/settings/edit-contact',
            'campaign/settings/geoip' => 'campaign/settings/edit-geoip',
            'campaign/settings/recaptcha' => 'campaign/settings/edit-recaptcha',
            'campaign/settings/campaigntypes' => 'campaign/campaign-types/index',
            'campaign/settings/campaigntypes/new' => 'campaign/campaign-types/edit',
            'campaign/settings/campaigntypes/<campaignTypeId:\d+>' => 'campaign/campaign-types/edit',
            'campaign/settings/mailinglisttypes' => 'campaign/mailing-list-types/index',
            'campaign/settings/mailinglisttypes/new' => 'campaign/mailing-list-types/edit',
            'campaign/settings/mailinglisttypes/<mailingListTypeId:\d+>' => 'campaign/mailing-list-types/edit',
        ];
    }

    /**
     * Registers the components that should be defined via settings, providing
     * they have not already been set in `$pluginConfigs`.
     *
     * @see Plugins::$pluginConfigs
     */
    private function _registerComponents(): void
    {
        $this->set('mailer', fn() => $this->createMailer());
    }

    /**
     * Registers variables.
     *
     * @since 2.0.0
     */
    private function _registerVariables(): void
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
     * Registers a custom log target, keeping the format as simple as possible.
     *
     * @see LineFormatter::SIMPLE_FORMAT
     */
    private function _registerLogTarget(): void
    {
        Craft::getLogger()->dispatcher->targets[] = new MonologTarget([
            'name' => 'campaign',
            'categories' => ['campaign'],
            'level' => LogLevel::INFO,
            'logContext' => false,
            'allowLineBreaks' => false,
            'formatter' => new LineFormatter(
                format: "[%datetime%] %message%\n",
                dateFormat: 'Y-m-d H:i:s',
            ),
        ]);
    }

    /**
     * Registers element types.
     */
    private function _registerElementTypes(): void
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
    private function _registerFieldTypes(): void
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
    private function _registerAfterInstallEvent(): void
    {
        Event::on(Plugins::class, Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function(PluginEvent $event) {
                if ($event->plugin === $this) {
                    // Don't proceed if plugin exists in incoming project config, otherwise updates won't be applied
                    // https://github.com/putyourlightson/craft-campaign/issues/191
                    if (Craft::$app->getProjectConfig()->get('plugins.campaign', true) !== null) {
                        return;
                    }

                    // Create and save default settings
                    $settings = $this->createSettingsModel();
                    Craft::$app->getPlugins()->savePluginSettings($this, $settings->getAttributes());

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
    private function _registerFieldEvents(): void
    {
        Event::on(Fields::class, Fields::EVENT_AFTER_SAVE_FIELD,
            function(FieldEvent $event) {
                if ($event->isNew === false) {
                    $this->segments->handleChangedField($event->field);
                }
            }
        );

        Event::on(Fields::class, Fields::EVENT_AFTER_DELETE_FIELD,
            function(FieldEvent $event) {
                $this->segments->handleDeletedField($event->field);
            }
        );
    }

    /**
     * Registers event listeners for project config changes.
     */
    private function _registerProjectConfigListeners(): void
    {
        // Contact field layout
        Craft::$app->getProjectConfig()
            ->onAdd(ContactsService::CONFIG_CONTACTFIELDLAYOUT_KEY, [$this->contacts, 'handleChangedContactFieldLayout'])
            ->onUpdate(ContactsService::CONFIG_CONTACTFIELDLAYOUT_KEY, [$this->contacts, 'handleChangedContactFieldLayout'])
            ->onRemove(ContactsService::CONFIG_CONTACTFIELDLAYOUT_KEY, [$this->contacts, 'handleChangedContactFieldLayout']);

        // Campaign types
        Craft::$app->getProjectConfig()
            ->onAdd(CampaignTypesService::CONFIG_CAMPAIGNTYPES_KEY . '.{uid}', [$this->campaignTypes, 'handleChangedCampaignType'])
            ->onUpdate(CampaignTypesService::CONFIG_CAMPAIGNTYPES_KEY . '.{uid}', [$this->campaignTypes, 'handleChangedCampaignType'])
            ->onRemove(CampaignTypesService::CONFIG_CAMPAIGNTYPES_KEY . '.{uid}', [$this->campaignTypes, 'handleDeletedCampaignType']);
        Event::on(Sites::class, Sites::EVENT_AFTER_DELETE_SITE, [$this->campaignTypes, 'handleDeletedSite']);

        // Mailing list types
        Craft::$app->getProjectConfig()
            ->onAdd(MailingListTypesService::CONFIG_MAILINGLISTTYPES_KEY . '.{uid}', [$this->mailingListTypes, 'handleChangedMailingListType'])
            ->onUpdate(MailingListTypesService::CONFIG_MAILINGLISTTYPES_KEY . '.{uid}', [$this->mailingListTypes, 'handleChangedMailingListType'])
            ->onRemove(MailingListTypesService::CONFIG_MAILINGLISTTYPES_KEY . '.{uid}', [$this->mailingListTypes, 'handleDeletedMailingListType']);
        Event::on(Sites::class, Sites::EVENT_AFTER_DELETE_SITE, [$this->mailingListTypes, 'handleDeletedSite']);

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
    private function _registerTemplateHooks(): void
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
    private function _registerAllowedOrigins(): void
    {
        Event::on(PreviewController::class, Controller::EVENT_BEFORE_ACTION,
            function(ActionEvent $event) {
                if ($event->action->id === 'preview') {
                    $origins = Craft::$app->getRequest()->getOrigin();

                    if (empty($origins)) {
                        return;
                    }

                    $allowedOrigins = Campaign::$plugin->settings->allowedOrigins;

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
    private function _registerTwigExtensions(): void
    {
        Craft::$app->getView()->registerTwigExtension(new CampaignTwigExtension());
    }

    /**
     * Registers Feed Me elements.
     *
     * @since 2.8.0
     */
    private function _registerFeedMeElements(): void
    {
        // Only check that the class exists, disregarding application initialisation.
        // https://github.com/putyourlightson/craft-campaign/issues/428
        if (class_exists(FeedMeElements::class)) {
            Event::on(FeedMeElements::class, FeedMeElements::EVENT_REGISTER_FEED_ME_ELEMENTS,
                function(RegisterFeedMeElementsEvent $event) {
                    $event->elements[] = CampaignFeedMeElement::class;
                    $event->elements[] = ContactFeedMeElement::class;
                    $event->elements[] = MailingListFeedMeElement::class;
                }
            );
        }
    }

    /**
     * Registers native fields.
     *
     * @since 2.0.0
     */
    private function _registerNativeFields(): void
    {
        Event::on(FieldLayout::class, FieldLayout::EVENT_DEFINE_NATIVE_FIELDS,
            function(DefineFieldLayoutFieldsEvent $event) {
                /** @var FieldLayout $fieldLayout */
                $fieldLayout = $event->sender;

                switch ($fieldLayout->type) {
                    case CampaignElement::class:
                        $event->fields[] = CampaignTitleField::class;
                        break;
                    case MailingListElement::class:
                        $event->fields[] = NonTranslatableTitleField::class;
                        break;
                    case ContactElement::class:
                        $event->fields[] = ContactEmailFieldLayoutElement::class;
                        break;
                }
            }
        );
    }

    /**
     * Registers asset bundles.
     *
     * @since 2.0.0
     */
    private function _registerAssetBundles(): void
    {
        Craft::$app->getView()->registerAssetBundle(CpAsset::class);

        if (Craft::$app->getRequest()->getSegment(1) == 'campaign') {
            Craft::$app->getView()->registerAssetBundle(CampaignAsset::class);
        }
    }

    /**
     * Registers CP URL rules.
     *
     * @since 2.0.0
     */
    private function _registerCpUrlRules(): void
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
    private function _registerUtilities(): void
    {
        Event::on(Utilities::class, Utilities::EVENT_REGISTER_UTILITY_TYPES,
            function(RegisterComponentTypesEvent $event) {
                $event->types[] = CampaignUtility::class;
            }
        );
    }

    /**
     * Registers widgets.
     *
     * @since 2.4.0
     */
    private function _registerWidgets(): void
    {
        Event::on(Dashboard::class, Dashboard::EVENT_REGISTER_WIDGET_TYPES,
            function(RegisterComponentTypesEvent $event) {
                $event->types[] = CampaignStatsWidget::class;
                $event->types[] = MailingListStatsWidget::class;
            }
        );
    }

    /**
     * Registers user permissions.
     *
     * @since 2.0.0
     */
    private function _registerUserPermissions(): void
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

                $manageContactsLabel = Craft::t('campaign', 'Manage contacts');
                if (Craft::$app->getIsMultiSite()) {
                    $manageContactsLabel .= ' (' . Craft::t('campaign', 'requires edit permission for the primary site') . ')';
                }

                $permissions = [
                    'campaign:campaigns' => [
                        'label' => Craft::t('campaign', 'Manage campaigns'),
                        'nested' => $campaignTypePermissions,
                    ],
                    'campaign:contacts' => [
                        'label' => $manageContactsLabel,
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
                $permissions['campaign:reports'] = ['label' => Craft::t('campaign', 'View reports')];
                $permissions['campaign:settings'] = ['label' => Craft::t('campaign', 'Manage plugin settings')];

                $event->permissions[] = [
                    'heading' => $this->name,
                    'permissions' => $permissions,
                ];
            }
        );
    }
}
