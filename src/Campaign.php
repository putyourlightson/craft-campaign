<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign;

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
use craft\web\twig\variables\CraftVariable;
use yii\base\Event;

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
 * @method    SettingsModel getSettings()
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

        // Register variable
        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function(Event $event) {
            /** @var CraftVariable $variable */
            $variable = $event->sender;
            $variable->set('campaign', CampaignVariable::class);
        });

        // Register Twig extension
        Craft::$app->view->registerTwigExtension(new CampaignTwigExtension());

        // Register user permissions if edition is client or above
        if (Craft::$app->getEdition() >= Craft::Client) {
            Event::on(UserPermissions::class, UserPermissions::EVENT_REGISTER_PERMISSIONS, function(RegisterUserPermissionsEvent $event) {
                $event->permissions['Campaign'] = [
                    'campaign-sendSendouts' => ['label' => Craft::t('campaign', 'Send sendouts')],
                    'campaign-accessImport' => ['label' => Craft::t('campaign', 'Access import')],
                    'campaign-accessExport' => ['label' => Craft::t('campaign', 'Access export')],
                    'campaign-accessSettings' => ['label' => Craft::t('campaign', 'Access settings')],
                ];
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
            $event->rules['campaign/settings/campaigntypes/new'] = 'campaign/campaign-types/edit-campaign-type';
            $event->rules['campaign/settings/campaigntypes/<campaignTypeId:\d+>'] = 'campaign/campaign-types/edit-campaign-type';
            $event->rules['campaign/settings/mailinglisttypes/new'] = 'campaign/mailing-list-types/edit-mailing-list-type';
            $event->rules['campaign/settings/mailinglisttypes/<mailingListTypeId:\d+>'] = 'campaign/mailing-list-types/edit-mailing-list-type';
        });
    }

    /**
     * @inheritdoc
     */
    public function getCpNavItem()
    {
        $cpNavItem =  parent::getCpNavItem();

        $cpNavItem['subnav'] = [
            'reports' => ['label' => Craft::t('campaign', 'Reports'), 'url' => 'campaign/reports'],
            'campaigns' => ['label' => Craft::t('campaign', 'Campaigns'), 'url' => 'campaign/campaigns'],
            'contacts' => ['label' => Craft::t('campaign', 'Contacts'), 'url' => 'campaign/contacts'],
            'mailinglists' => ['label' => Craft::t('campaign', 'Mailing Lists'), 'url' => 'campaign/mailinglists'],
            'segments' => ['label' => Craft::t('campaign', 'Segments'), 'url' => 'campaign/segments'],
            'sendouts' => ['label' => Craft::t('campaign', 'Sendouts'), 'url' => 'campaign/sendouts'],
        ];

        $user = Craft::$app->getUser();

        // Show import/export if permission allows
        if ($user->checkPermission('campaign-accessImport') OR $user->checkPermission('campaign-accessExport')) {
            $cpNavItem['subnav']['import-export'] = ['label' => Craft::t('campaign', 'Import/Export'), 'url' => 'campaign/import-export'];
        }

        // Show settings if permission allows
        if ($user->checkPermission('campaign-accessSettings')) {
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
     * Returns true if lite version
     *
     * @return bool
     */
    public function isLite(): bool
    {
        return $this->packageName == 'putyourlightson/campaign-lite';
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
