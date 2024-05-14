<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\models;

use Craft;
use craft\base\FieldInterface;
use craft\base\Model;
use craft\behaviors\EnvAttributeParserBehavior;
use craft\behaviors\FieldLayoutBehavior;
use craft\helpers\App;
use craft\models\FieldLayout;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\fieldlayoutelements\contacts\ContactEmailFieldLayoutElement;
use putyourlightson\campaign\fieldlayoutelements\contacts\ContactFieldLayoutTab;
use yii\validators\EmailValidator;

/**
 * @mixin FieldLayoutBehavior
 *
 * @property-read FieldLayout $contactFieldLayout
 * @property-read FieldInterface[] $contactFields
 * @property-read ContactElement[] $defaultNotificationContacts
 * @property-read string $emailFieldLabel
 */
class SettingsModel extends Model
{
    /**
     * @var bool Whether to enable anonymous tracking of opens and clicks
     */
    public bool $enableAnonymousTracking = false;

    /**
     * @var bool Sendout emails will be saved into local files (in storage/runtime/debug/mail) rather that actually being sent
     */
    public bool $testMode = false;

    /**
     * @var string An API key to use for triggering tasks and notifications (min. 16 characters)
     */
    public string $apiKey;

    /**
     * @var bool Whether one-click unsubscribe headers should be added to emails.
     * @since 2.15.0
     */
    public bool $addOneClickUnsubscribeHeaders = true;

    /**
     * @var bool Whether to validate incoming webhook requests using a signing key or secret
     * @since 2.10.0
     */
    public bool $validateWebhookRequests = false;

    /**
     * @var string|null A webhook signing secret provided by MailerSend to validate incoming webhook requests
     * @since 2.10.0
     */
    public ?string $mailersendWebhookSigningSecret = null;

    /**
     * @var string|null A webhook signing key provided by Mailgun to validate incoming webhook requests
     * @since 1.19.0
     */
    public ?string $mailgunWebhookSigningKey = null;

    /**
     * @var string|null A webhook verification key provided by SendGrid to validate incoming webhook requests
     * @since 2.10.0
     */
    public ?string $sendgridWebhookVerificationKey = null;

    /**
     * @var array|null The allowed IP addresses for incoming webhook requests from Postmark
     * @link https://postmarkapp.com/support/article/800-ips-for-firewalls#webhooks
     * @since 1.19.0
     */
    public ?array $postmarkAllowedIpAddresses = [
        '3.134.147.250',
        '50.31.156.6',
        '50.31.156.77',
        '18.217.206.57',
    ];

    /**
     * @var array|null The AJAX origins that should be allowed to access live preview.
     * @since 1.21.0
     */
    public ?array $allowedOrigins = null;

    /**
     * @var bool Whether verification emails should be sent via the Craft mailer, instead of the Campaign mailer
     * @since 1.22.0
     */
    public bool $sendVerificationEmailsViaCraft = false;

    /**
     * @var array|null The names and emails that sendouts can be sent from
     */
    public ?array $fromNamesEmails = null;

    /**
     * @var string|null The transport type that should be used
     */
    public ?string $transportType = null;

    /**
     * @var array|null The transport type’s settings
     */
    public ?array $transportSettings = null;

    /**
     * @var array|null Default notification contact IDs
     */
    public ?array $defaultNotificationContactIds = null;

    /**
     * @var bool Whether the title field should be shown for sendouts
     */
    public bool $showSendoutTitleField = false;

    /**
     * @var int|null The batch size to use for sendout jobs
     */
    public ?int $sendoutJobBatchSize = 100;

    /**
     * @var int|null The amount of time in seconds to delay between sendout job batches
     */
    public ?int $sendoutJobBatchDelay = 0;

    /**
     * @var int|null The maximum size of sendout batches
     * @deprecated in 2.13.0.
     */
    public ?int $maxBatchSize = 10000;

    /**
     * @var int The amount of time in seconds to delay jobs between sendout batches
     * @deprecated in 2.13.0.
     */
    public int $batchJobDelay = 10;

    /**
     * @var string|null The memory usage limit per sendout batch in bytes or a shorthand byte value (set to -1 for unlimited)
     * @deprecated in 2.13.0.
     */
    public ?string $memoryLimit = '1024M';

    /**
     * @var int|null The execution time limit per sendout batch in seconds (set to 0 for unlimited)
     * @deprecated in 2.13.0.
     */
    public ?int $timeLimit = 3600;

    /**
     * @var float The threshold for memory usage per sendout batch as a fraction
     * @deprecated in 2.13.0.
     */
    public float $memoryThreshold = 0.8;

    /**
     * @var float The threshold for execution time per sendout batch as a fraction
     * @deprecated in 2.13.0.
     */
    public float $timeThreshold = 0.8;

    /**
     * @var int The maximum number of times to attempt sending a sendout to a single contact before failing
     * @since 1.10.0
     */
    public int $maxSendAttempts = 3;

    /**
     * @var int The maximum number of failed attempts to send to contacts that are allowed before failing the entire sendout
     * @since 1.15.4
     */
    public int $maxSendFailuresAllowed = 1;

    /**
     * @var int The maximum number of times to attempt retrying a failed sendout job
     */
    public int $maxRetryAttempts = 10;

    /**
     * The amount of time in seconds to reserve a sendout job.
     *
     * @since 1.9.0
     */
    public int $sendoutJobTtr = 300;

    /**
     * The priority to give the sendout cache job (the lower the number, the higher the priority).
     *
     * @since 2.9.0
     */
    public ?int $sendoutJobPriority = null;

    /**
     * The amount of time in seconds to reserve an import job.
     *
     * @since 2.10.0
     */
    public int $importJobTtr = 300;

    /**
     * The priority to give the import job (the lower the number, the higher the priority).
     *
     * @since 2.11.0
     */
    public int $importJobPriority = 2048;

    /**
     * @var int The amount of time in seconds to reserve a sync job
     * @since 2.10.0
     */
    public int $syncJobTtr = 300;

    /**
     * The priority to give the sync job (the lower the number, the higher the priority).
     *
     * @since 2.11.1
     */
    public int $syncJobPriority = 2048;

    /**
     * @var bool Enable GeoIP to geolocate contacts by their IP addresses
     */
    public bool $geoIp = false;

    /**
     * @var string|null The ipstack.com API key
     */
    public ?string $ipstackApiKey = null;

    /**
     * @var bool Enable reCAPTCHA to protect mailing list subscription forms from bots
     */
    public bool $reCaptcha = false;

    /**
     * @var string|null The reCAPTCHA site key
     */
    public ?string $reCaptchaSiteKey = null;

    /**
     * @var string|null The reCAPTCHA secret key
     */
    public ?string $reCaptchaSecretKey = null;

    /**
     * @var string The reCAPTCHA error message
     */
    public string $reCaptchaErrorMessage = 'Your form submission was flagged as spam by Google reCAPTCHA. Please go back and try again.';

    /**
     * @var bool Enable Turnstile to protect mailing list subscription forms from bots
     */
    public bool $turnstile = false;

    /**
     * @var string|null The Turnstile site key
     */
    public ?string $turnstileSiteKey = null;

    /**
     * @var string|null The Turnstile secret key
     */
    public ?string $turnstileSecretKey = null;

    /**
     * @var string The Turnstile error message
     */
    public string $turnstileErrorMessage = 'Your form submission was flagged as spam by Turnstile. Please go back and try again.';

    /**
     * @var int The maximum number of pending contacts to store per email address and mailing list
     */
    public int $maxPendingContacts = 5;

    /**
     * @var mixed The amount of time to wait before purging pending contacts in seconds or as an interval (0 for disabled)
     */
    public mixed $purgePendingContactsDuration = 0;

    /**
     * @var array Extra fields and the operators that should be available to segments
     * @since 1.7.3
     */
    public array $extraSegmentFieldOperators = [];

    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {
        return [
            'parser' => [
                'class' => EnvAttributeParserBehavior::class,
                'attributes' => ['apiKey'],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        return [
            [['apiKey', 'fromNamesEmails', 'transportType', 'sendoutJobBatchSize', 'sendoutJobBatchDelay'], 'required'],
            [['apiKey'], 'string', 'length' => [16]],
            [['fromNamesEmails'], 'validateFromNamesEmails'],
            [['sendoutJobBatchSize'], 'integer', 'min' => 1],
            [['sendoutJobBatchDelay'], 'integer', 'min' => 0],
            [['ipstackApiKey'], 'required', 'when' => fn(SettingsModel $model) => $model->geoIp],
            [['reCaptchaSiteKey', 'reCaptchaSecretKey', 'reCaptchaErrorMessage'], 'required', 'when' => fn(SettingsModel $model) => $model->reCaptcha],
            [['turnstileSiteKey', 'turnstileSecretKey', 'turnstileErrorMessage'], 'required', 'when' => fn(SettingsModel $model) => $model->turnstile],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        $labels = parent::attributeLabels();

        // Set the field labels
        $labels['apiKey'] = Craft::t('campaign', 'API Key');
        $labels['fromNamesEmails'] = Craft::t('campaign', 'From Names and Emails');
        $labels['ipstackApiKey'] = Craft::t('campaign', 'ipstack.com API Key');
        $labels['reCaptchaSiteKey'] = Craft::t('campaign', 'reCAPTCHA Site Key');
        $labels['reCaptchaSecretKey'] = Craft::t('campaign', 'reCAPTCHA Secret Key');
        $labels['reCaptchaErrorMessage'] = Craft::t('campaign', 'reCAPTCHA Error Message');
        $labels['turnstileSiteKey'] = Craft::t('campaign', 'Turnstile Site Key');
        $labels['turnstileSecretKey'] = Craft::t('campaign', 'Turnstile Secret Key');
        $labels['turnstileErrorMessage'] = Craft::t('campaign', 'Turnstile Error Message');

        return $labels;
    }

    /**
     * Returns the parsed API key.
     *
     * @since 2.16.0
     */
    public function getApiKey(): string
    {
        return App::parseEnv($this->apiKey) ?? '';
    }

    /**
     * Returns the parsed MailerSend webhook signing secret.
     *
     * @since 2.16.0
     */
    public function getMailersendWebhookSigningSecret(): string
    {
        return App::parseEnv($this->mailersendWebhookSigningSecret) ?? '';
    }

    /**
     * Returns the parsed Mailgun webhook signing secret.
     *
     * @since 2.16.0
     */
    public function getMailgunWebhookSigningSecret(): string
    {
        return App::parseEnv($this->mailgunWebhookSigningKey) ?? '';
    }

    /**
     * Returns the parsed SendGrid webhook signing secret.
     *
     * @since 2.16.0
     */
    public function getSendgridWebhookSigningSecret(): string
    {
        return App::parseEnv($this->sendgridWebhookVerificationKey) ?? '';
    }

    /**
     * Returns the parsed ipstack.com API key.
     *
     * @since 2.16.0
     */
    public function getIpstackApiKey(): string
    {
        return App::parseEnv($this->ipstackApiKey) ?? '';
    }

    /**
     * Returns the parsed reCAPTCHA site key.
     *
     * @since 2.16.0
     */
    public function getRecaptchaSiteKey(): string
    {
        return App::parseEnv($this->reCaptchaSiteKey) ?? '';
    }

    /**
     * Returns the parsed reCAPTCHA secret key.
     *
     * @since 2.16.0
     */
    public function getRecaptchaSecretKey(): string
    {
        return App::parseEnv($this->reCaptchaSecretKey) ?? '';
    }

    /**
     * Returns the parsed reCAPTCHA error message.
     *
     * @since 2.16.0
     */
    public function getRecaptchaErrorMessage(): string
    {
        return App::parseEnv($this->reCaptchaErrorMessage) ?? '';
    }

    /**
     * Returns the parsed Turnstile site key.
     *
     * @since 2.16.0
     */
    public function getTurnstileSiteKey(): string
    {
        return App::parseEnv($this->turnstileSiteKey) ?? '';
    }

    /**
     * Returns the parsed Turnstile secret key.
     *
     * @since 2.16.0
     */
    public function getTurnstileSecretKey(): string
    {
        return App::parseEnv($this->turnstileSecretKey) ?? '';
    }

    /**
     * Returns the parsed Turnstile error message.
     *
     * @since 2.16.0
     */
    public function getTurnstileErrorMessage(): string
    {
        return App::parseEnv($this->turnstileErrorMessage) ?? '';
    }

    /**
     * Returns the contact field layout.
     */
    public function getContactFieldLayout(): FieldLayout
    {
        $fieldLayout = Craft::$app->getFields()->getLayoutByType(ContactElement::class);

        // Ensure email field exists
        if (!$fieldLayout->isFieldIncluded('email')) {
            $tabs = $fieldLayout->getTabs();

            if (empty($tabs)) {
                $tab = new ContactFieldLayoutTab();
            } else {
                $tab = $tabs[0];
                $emailElement = new ContactEmailFieldLayoutElement();
                $tab->setElements(array_merge([$emailElement], $tab->getElements()));
            }

            $fieldLayout->setTabs([$tab]);
            Craft::$app->getFields()->saveLayout($fieldLayout);
        }

        return $fieldLayout;
    }

    /**
     * Returns the email field label.
     */
    public function getEmailFieldLabel(): string
    {
        $fieldLayout = $this->getContactFieldLayout();
        $field = $fieldLayout->getField('email');

        return $field->label();
    }

    /**
     * Returns the contact's custom fields.
     *
     * @return FieldInterface[]
     */
    public function getContactFields(): array
    {
        return $this->getContactFieldLayout()->getCustomFields();
    }

    /**
     * Validates the “from” names and emails.
     */
    public function validateFromNamesEmails(mixed $attribute): void
    {
        if (empty($this->fromNamesEmails)) {
            $this->addError($attribute, Craft::t('campaign', 'You must enter at least one name and email.'));
            return;
        }

        foreach ($this->fromNamesEmails as $fromNameEmail) {
            if ($fromNameEmail[0] === '' || $fromNameEmail[1] === '') {
                $this->addError($attribute, Craft::t('campaign', 'The name and email cannot be blank.'));
                return;
            }

            $emailValidator = new EmailValidator();

            if (!$emailValidator->validate($fromNameEmail[1])) {
                $this->addError($attribute, Craft::t('campaign', 'An invalid email was entered.'));
                return;
            }

            if ($fromNameEmail[2] && !$emailValidator->validate($fromNameEmail[2])) {
                $this->addError($attribute, Craft::t('campaign', 'An invalid email was entered.'));
                return;
            }
        }
    }

    /**
     * Returns the default notification contacts.
     *
     * @return ContactElement[]
     */
    public function getDefaultNotificationContacts(): array
    {
        if (empty($this->defaultNotificationContactIds)) {
            return [];
        }

        return Campaign::$plugin->contacts->getContactsByIds($this->defaultNotificationContactIds);
    }
}
