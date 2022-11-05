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
use craft\models\FieldLayout;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\ContactElement;
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
     * @var bool Whether to enable anonymous tracking
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
     * @var string|null A webhook signing key provided by Mailgun to validate incoming webhook requests
     * @since 1.19.0
     */
    public ?string $mailgunWebhookSigningKey = null;

    /**
     * @var array|null The allowed IP addresses for incoming webhook requests from Postmark
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
     * @var int|null The maximum size of sendout batches
     */
    public ?int $maxBatchSize = 10000;

    /**
     * @var string|null The memory usage limit per sendout batch in bytes or a shorthand byte value (set to -1 for unlimited)
     */
    public ?string $memoryLimit = '1024M';

    /**
     * @var int|null The execution time limit per sendout batch in seconds (set to 0 for unlimited)
     */
    public ?int $timeLimit = 3600;

    /**
     * @var float The threshold for memory usage per sendout batch as a fraction
     */
    public float $memoryThreshold = 0.8;

    /**
     * @var float The threshold for execution time per sendout batch as a fraction
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
     * @var int The amount of time in seconds to delay jobs between sendout batches
     */
    public int $batchJobDelay = 10;

    /**
     * @var int The amount of time in seconds to reserve a sendout job
     * @since 1.9.0
     */
    public int $sendoutJobTtr = 300;

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
    public string $reCaptchaErrorMessage = 'Your form submission was blocked by Google reCAPTCHA. Please go back and try again.';

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
            [['apiKey', 'fromNamesEmails', 'transportType', 'maxBatchSize', 'memoryLimit', 'timeLimit'], 'required'],
            [['apiKey'], 'string', 'length' => [16]],
            [['fromNamesEmails'], 'validateFromNamesEmails'],
            [['maxBatchSize', 'timeLimit'], 'integer'],
            [['ipstackApiKey'], 'required', 'when' => fn(SettingsModel $model) => $model->geoIp],
            [['reCaptchaSiteKey', 'reCaptchaSecretKey', 'reCaptchaErrorMessage'], 'required', 'when' => fn(SettingsModel $model) => $model->reCaptcha],
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

        return $labels;
    }

    /**
     * Returns the contact field layout.
     */
    public function getContactFieldLayout(): FieldLayout
    {
        $fieldLayout = Craft::$app->getFields()->getLayoutByType(ContactElement::class);

        // Ensure email field exists
        if (!$fieldLayout->isFieldIncluded('email')) {
            $fieldLayout->setTabs([
                new ContactFieldLayoutTab(),
            ]);
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
     * Validates the from names and emails.
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
