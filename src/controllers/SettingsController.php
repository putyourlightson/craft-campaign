<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\controllers;

use Craft;
use craft\elements\User;
use craft\errors\MissingComponentException;
use craft\helpers\ArrayHelper;
use craft\helpers\MailerHelper;
use craft\mail\transportadapters\BaseTransportAdapter;
use craft\mail\transportadapters\Sendmail;
use craft\mail\transportadapters\TransportAdapterInterface;
use craft\web\UrlManager;
use putyourlightson\campaign\base\BaseSettingsController;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\helpers\SendoutHelper;
use putyourlightson\campaign\helpers\SettingsHelper;
use putyourlightson\campaign\models\SettingsModel;
use yii\web\Response;

class SettingsController extends BaseSettingsController
{
    public function actionIndex(): Response
    {
        return $this->redirect('campaign/settings/general');
    }

    /**
     * Edit general settings.
     *
     * @param SettingsModel|null $settings The settings being edited, if there were any validation errors.
     */
    public function actionEditGeneral(SettingsModel $settings = null): Response
    {
        if ($settings === null) {
            $settings = Campaign::$plugin->settings;
        }

        return $this->renderTemplate('campaign/_settings/general', [
            'settings' => $settings,
            'config' => Craft::$app->getConfig()->getConfigFromFile('campaign'),
            'phpBinPath' => '/usr/bin/php',
            'isDynamicWebAliasUsed' => SettingsHelper::isDynamicWebAliasUsed(),
        ]);
    }

    /**
     * Edit email settings.
     *
     * @param SettingsModel|null $settings The settings being edited, if there were any validation errors.
     * @param TransportAdapterInterface|null $adapter The transport adapter, if there were any validation errors.
     */
    public function actionEditEmail(SettingsModel $settings = null, TransportAdapterInterface $adapter = null): Response
    {
        if ($settings === null) {
            $settings = Campaign::$plugin->settings;
        }

        if ($adapter === null) {
            $settings->transportType = $settings->transportType ?: Sendmail::class;
            try {
                $adapter = MailerHelper::createTransportAdapter($settings->transportType, $settings->transportSettings);
            } catch (MissingComponentException) {
                $adapter = new Sendmail();
                $adapter->addError('type', Craft::t('app', 'The transport type “{type}” could not be found.', [
                    'type' => $settings->transportType,
                ]));
            }
        }

        // Get all the registered transport adapter types
        $allTransportAdapterTypes = MailerHelper::allMailerTransportTypes();

        // Make sure the selected adapter class is in there
        if (!in_array(get_class($adapter), $allTransportAdapterTypes)) {
            $allTransportAdapterTypes[] = get_class($adapter);
        }

        $allTransportAdapters = [];
        $transportTypeOptions = [];

        foreach ($allTransportAdapterTypes as $transportAdapterType) {
            /** @var string|TransportAdapterInterface $transportAdapterType */
            if ($transportAdapterType === get_class($adapter) || $transportAdapterType::isSelectable()) {
                $allTransportAdapters[] = MailerHelper::createTransportAdapter($transportAdapterType);
                $transportTypeOptions[] = [
                    'value' => $transportAdapterType,
                    'label' => $transportAdapterType::displayName(),
                ];
            }
        }

        // Sort them by name
        ArrayHelper::multisort($transportTypeOptions, 'label');

        return $this->renderTemplate('campaign/_settings/email', [
            'settings' => $settings,
            'config' => Craft::$app->getConfig()->getConfigFromFile('campaign'),
            'siteOptions' => SettingsHelper::getSiteOptions(),
            'adapter' => $adapter,
            'allTransportAdapters' => $allTransportAdapters,
            'transportTypeOptions' => $transportTypeOptions,
        ]);
    }

    /**
     * Edit contact settings.
     *
     * @param SettingsModel|null $settings The settings being edited, if there were any validation errors.
     */
    public function actionEditContact(SettingsModel $settings = null): Response
    {
        if ($settings === null) {
            $settings = Campaign::$plugin->settings;
        }

        return $this->renderTemplate('campaign/_settings/contact', [
            'settings' => $settings,
            'fieldLayout' => $settings->getContactFieldLayout(),
            'config' => Craft::$app->getConfig()->getConfigFromFile('campaign'),
        ]);
    }

    /**
     * Edit sendout settings.
     *
     * @param SettingsModel|null $settings The settings being edited, if there were any validation errors.
     */
    public function actionEditSendout(SettingsModel $settings = null): Response
    {
        if ($settings === null) {
            $settings = Campaign::$plugin->settings;
        }

        $memoryLimit = $settings->memoryLimit ? SendoutHelper::memoryInBytes($settings->memoryLimit) : 0;

        return $this->renderTemplate('campaign/_settings/sendout', [
            'settings' => $settings,
            'config' => Craft::$app->getConfig()->getConfigFromFile('campaign'),
            'contactElementType' => ContactElement::class,
            'system' => [
                'memoryLimit' => ini_get('memory_limit'),
                'memoryLimitExceeded' => $memoryLimit > SendoutHelper::memoryInBytes(ini_get('memory_limit')),
                'timeLimit' => ini_get('max_execution_time'),
            ],
        ]);
    }

    /**
     * Edit GeoIP settings.
     *
     * @param SettingsModel|null $settings The settings being edited, if there were any validation errors.
     */
    public function actionEditGeoip(SettingsModel $settings = null): Response
    {
        if ($settings === null) {
            $settings = Campaign::$plugin->settings;
        }

        return $this->renderTemplate('campaign/_settings/geoip', [
            'settings' => $settings,
            'config' => Craft::$app->getConfig()->getConfigFromFile('campaign'),
        ]);
    }

    /**
     * Edit Recaptcha settings.
     *
     * @param SettingsModel|null $settings The settings being edited, if there were any validation errors.
     */
    public function actionEditRecaptcha(SettingsModel $settings = null): Response
    {
        if ($settings === null) {
            $settings = Campaign::$plugin->settings;
        }

        return $this->renderTemplate('campaign/_settings/recaptcha', [
            'settings' => $settings,
            'config' => Craft::$app->getConfig()->getConfigFromFile('campaign'),
        ]);
    }

    /**
     * Saves general settings.
     */
    public function actionSaveGeneral(): ?Response
    {
        $this->requirePostRequest();

        $settings = Campaign::$plugin->settings;

        // Set the simple stuff
        $settings->testMode = $this->request->getBodyParam('testMode', $settings->testMode);
        $settings->apiKey = $this->request->getBodyParam('apiKey', $settings->apiKey);
        $settings->mailgunWebhookSigningKey = $this->request->getBodyParam('mailgunWebhookSigningKey', $settings->mailgunWebhookSigningKey);

        // Save it
        if (!Craft::$app->getPlugins()->savePluginSettings(Campaign::$plugin, $settings->getAttributes())) {
            return $this->asModelFailure($settings, Craft::t('campaign', 'Couldn’t save general settings.'), 'settings');
        }

        return $this->asSuccess(Craft::t('campaign', 'General settings saved.'));
    }

    /**
     * Saves email settings.
     */
    public function actionSaveEmail(): ?Response
    {
        $this->requirePostRequest();

        $settings = $this->_getEmailSettingsFromPost();

        // Create the transport adapter so that we can validate it
        /** @var BaseTransportAdapter $adapter */
        $adapter = MailerHelper::createTransportAdapter($settings->transportType, $settings->transportSettings);

        // Validate transport adapter
        $adapter->validate();

        // Save it
        if ($adapter->hasErrors() || !Craft::$app->getPlugins()->savePluginSettings(Campaign::$plugin, $settings->getAttributes())) {
            return $this->asModelFailure($settings, Craft::t('campaign', 'Couldn’t save email settings.'), 'settings', [], [
                'adapter' => $adapter,
            ]);
        }

        return $this->asSuccess(Craft::t('campaign', 'Email settings saved.'));
    }

    /**
     * Saves contact settings.
     */
    public function actionSaveContact(): ?Response
    {
        $this->requirePostRequest();

        $settings = Campaign::$plugin->settings;

        $settings->enableAnonymousTracking = Craft::$app->getRequest()->getBodyParam('enableAnonymousTracking', $settings->enableAnonymousTracking);

        // Set the field layout
        $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();
        $fieldLayout->type = ContactElement::class;

        // Save it
        if (!Craft::$app->getPlugins()->savePluginSettings(Campaign::$plugin, $settings->getAttributes())
            || !Campaign::$plugin->contacts->saveContactFieldLayout($fieldLayout)
        ) {
            return $this->asFailure(Craft::t('campaign', 'Couldn’t save contact settings.'));
        }

        return $this->asSuccess(Craft::t('campaign', 'Contact settings saved.'));
    }

    /**
     * Saves sendout settings.
     */
    public function actionSaveSendout(): ?Response
    {
        $this->requirePostRequest();

        $settings = Campaign::$plugin->settings;

        // Set the simple stuff
        $settings->defaultNotificationContactIds = $this->request->getBodyParam('defaultNotificationContactIds', $settings->defaultNotificationContactIds) ?: null;
        $settings->showSendoutTitleField = $this->request->getBodyParam('showSendoutTitleField', $settings->showSendoutTitleField) ?: false;
        $settings->maxBatchSize = $this->request->getBodyParam('maxBatchSize', $settings->maxBatchSize) ?: null;
        $settings->memoryLimit = $this->request->getBodyParam('memoryLimit', $settings->memoryLimit) ?: null;
        $settings->timeLimit = $this->request->getBodyParam('timeLimit', $settings->timeLimit) ?: null;

        // Save it
        if (!Craft::$app->getPlugins()->savePluginSettings(Campaign::$plugin, $settings->getAttributes())) {
            return $this->asModelFailure($settings, Craft::t('campaign', 'Couldn’t save sendout settings.'), 'settings');
        }

        return $this->asSuccess(Craft::t('campaign', 'Sendout settings saved.'));
    }

    /**
     * Saves GeoIP settings.
     */
    public function actionSaveGeoip(): ?Response
    {
        $this->requirePostRequest();

        $settings = Campaign::$plugin->settings;

        // Set the simple stuff
        $settings->geoIp = $this->request->getBodyParam('geoIp', $settings->geoIp);
        $settings->ipstackApiKey = $this->request->getBodyParam('ipstackApiKey', $settings->ipstackApiKey);

        // Save it
        if (!Craft::$app->getPlugins()->savePluginSettings(Campaign::$plugin, $settings->getAttributes())) {
            return $this->asModelFailure($settings, Craft::t('campaign', 'Couldn’t save GeoIP settings.'), 'settings');
        }

        return $this->asSuccess(Craft::t('campaign', 'GeoIP settings saved.'));
    }

    /**
     * Saves Recaptcha settings.
     */
    public function actionSaveRecaptcha(): ?Response
    {
        $this->requirePostRequest();

        $settings = Campaign::$plugin->settings;

        // Set the simple stuff
        $settings->reCaptcha = $this->request->getBodyParam('reCaptcha', $settings->reCaptcha);
        $settings->reCaptchaSiteKey = $this->request->getBodyParam('reCaptchaSiteKey', $settings->reCaptchaSiteKey);
        $settings->reCaptchaSecretKey = $this->request->getBodyParam('reCaptchaSecretKey', $settings->reCaptchaSecretKey);
        $settings->reCaptchaErrorMessage = $this->request->getBodyParam('reCaptchaErrorMessage', $settings->reCaptchaErrorMessage);

        // Save it
        if (!Craft::$app->getPlugins()->savePluginSettings(Campaign::$plugin, $settings->getAttributes())) {
            return $this->asModelFailure($settings, Craft::t('campaign', 'Couldn’t save reCAPTCHA settings.'), 'settings');
        }

        return $this->asSuccess(Craft::t('campaign', 'reCAPTCHA settings saved.'));
    }

    /**
     * Sends a test email.
     */
    public function actionSendTestEmail(): ?Response
    {
        $this->requirePostRequest();

        $settings = $this->_getEmailSettingsFromPost();

        // Create the transport adapter so that we can validate it
        /** @var BaseTransportAdapter $adapter */
        $adapter = MailerHelper::createTransportAdapter($settings->transportType, $settings->transportSettings);

        // Validate settings and transport adapter
        $settings->validate();
        $adapter->validate();

        if ($settings->hasErrors() || $adapter->hasErrors()) {
            return $this->asModelFailure($settings, Craft::t('campaign', 'Couldn’t send test email.'), 'settings', [], [
                'adapter' => $adapter,
            ]);
        }

        // Create mailer with settings
        $mailer = Campaign::$plugin->createMailer($settings);

        // Get from name and email
        $fromNameEmail = SettingsHelper::getFromNameEmail();

        $subject = Craft::t('campaign', 'This is a test email from Craft Campaign');
        $body = Craft::t('campaign', 'Congratulations! Craft Campaign was successfully able to send an email.');

        /** @var User $user */
        $user = Craft::$app->getUser()->getIdentity();

        $message = $mailer->compose()
            ->setFrom([$fromNameEmail['email'] => $fromNameEmail['name']])
            ->setTo($user->email)
            ->setSubject($subject)
            ->setHtmlBody($body)
            ->setTextBody($body);

        if ($fromNameEmail['replyTo']) {
            $message->setReplyTo($fromNameEmail['replyTo']);
        }

        if (!$message->send()) {
            return $this->asModelFailure($settings, Craft::t('campaign', 'Couldn’t send test email.'), 'settings', [], [
                'adapter' => $adapter,
            ]);
        }

        $this->setSuccessFlash(Craft::t('app', 'Email sent successfully! Check your inbox.'));

        // Send the settings and adapter back to the template
        /** @phpstan-var UrlManager $urlManager */
        $urlManager = Craft::$app->getUrlManager();
        $urlManager->setRouteParams([
            'settings' => $settings,
            'adapter' => $adapter,
        ]);

        return null;
    }

    /**
     * Returns email settings populated with post data.
     */
    private function _getEmailSettingsFromPost(): SettingsModel
    {
        $settings = Campaign::$plugin->settings;
        $settings->fromNamesEmails = $this->request->getBodyParam('fromNamesEmails', $settings->fromNamesEmails);
        $settings->transportType = $this->request->getBodyParam('transportType', $settings->transportType);
        $settings->transportSettings = $this->request->getBodyParam('transportTypes.' . $settings->transportType);

        return $settings;
    }
}
