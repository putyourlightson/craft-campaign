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
use putyourlightson\campaign\elements\ContactElement;
use yii\validators\EmailValidator;

/**
 * SettingsModel
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 *
 * @mixin FieldLayoutBehavior
 *
 * @property FieldLayout $contactFieldLayout
 * @property FieldInterface[] $contactFields
 */
class SettingsModel extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * @var bool Sendout emails will be saved into local files (in storage/runtime/debug/mail) rather that actually being sent
     */
    public $testMode = false;

    /**
     * @var string An API key to use for triggering tasks and notifications (min. 16 characters)
     */
    public $apiKey;

    /**
     * @var bool Whether one-click unsubscribe headers should be added to emails.
     * @since 1.26.0
     */
    public $addOneClickUnsubscribeHeaders = true;

    /**
     * @var string|null A webhook signing key provided by Mailgun to validate incoming webhook requests
     * @since 1.19.0
     */
    public $mailgunWebhookSigningKey;

    /**
     * @var array The allowed IP addresses for incoming webhook requests from Postmark
     * @since 1.19.0
     */
    public $postmarkAllowedIpAddresses = [
        '3.134.147.250',
        '50.31.156.6',
        '50.31.156.77',
        '18.217.206.57',
    ];

    /**
     * @var array|null The AJAX origins that should be allowed to access live preview.
     * @since 1.21.0
     */
    public $allowedOrigins;

    /**
     * @var bool Whether verification emails should be sent via the Craft mailer, instead of the Campaign mailer
     * @since 1.22.0
     */
    public $sendVerificationEmailsViaCraft = false;

    /**
     * @var array|null The names and emails that sendouts can be sent from
     */
    public $fromNamesEmails;

    /**
     * @var string|null The transport type that should be used
     */
    public $transportType;

    /**
     * @var array|null The transport type’s settings
     */
    public $transportSettings;

    /**
     * @var string A label to use for the email field
     */
    public $emailFieldLabel = 'Email';

    /**
     * TODO: remove in version 2.0.0
     *
     * @var int|null Contact field layout ID
     * @deprecated Since 1.15.0
     */
    public $contactFieldLayoutId;

    /**
     * @var int The maximum size of sendout batches
     */
    public $maxBatchSize = 10000;

    /**
     * @var mixed The memory usage limit per sendout batch in bytes or a shorthand byte value (set to -1 for unlimited)
     */
    public $memoryLimit = '1024M';

    /**
     * @var int The execution time limit per sendout batch in seconds (set to 0 for unlimited)
     */
    public $timeLimit = 3600;

    /**
     * @var float The threshold for memory usage per sendout batch as a fraction
     */
    public $memoryThreshold = 0.8;

    /**
     * @var float The threshold for execution time per sendout batch as a fraction
     */
    public $timeThreshold = 0.8;

    /**
     * @var int The maximum number of times to attempt sending a sendout to a single contact before failing
     * @since 1.10.0
     */
    public $maxSendAttempts = 3;

    /**
     * @var int The maximum number of failed attempts to send to contacts that are allowed before failing the entire sendout
     * @since 1.15.4
     */
    public $maxSendFailsAllowed = 1;

    /**
     * @var int The maximum number of times to attempt retrying a failed sendout job
     */
    public $maxRetryAttempts = 10;

    /**
     * @var int The amount of time in seconds to delay jobs between sendout batches
     */
    public $batchJobDelay = 10;

    /**
     * @var int The amount of time in seconds to reserve a sendout job
     * @since 1.9.0
     */
    public $sendoutJobTtr = 300;

    /**
     * The priority to give the sendout cache job (the lower the number, the higher the priority)
     *
     * @since 1.25.0
     */
    public $sendoutJobPriority = null;

    /**
     * @var bool Enable GeoIP to geolocate contacts by their IP addresses
     */
    public $geoIp = false;

    /**
     * @var string|null The ipstack.com API key
     */
    public $ipstackApiKey;

    /**
     * @var bool Enable reCAPTCHA to protect mailing list subscription forms from bots
     */
    public $reCaptcha = false;

    /**
     * TODO: change to `3` in version 2.0.0
     *
     * @var int|null The reCAPTCHA version
     */
    public $reCaptchaVersion = 2;

    /**
     * @var string|null The reCAPTCHA site key
     */
    public $reCaptchaSiteKey;

    /**
     * @var string|null The reCAPTCHA secret key
     */
    public $reCaptchaSecretKey;

    /**
     * @var string The reCAPTCHA error message
     */
    public $reCaptchaErrorMessage = 'Your form submission was blocked by Google reCAPTCHA. Please go back and try again.';

    /**
     * TODO: remove in version 2.0.0
     *
     * @var string|null The size of the reCAPTCHA widget
     */
    public $reCaptchaSize;

    /**
     * TODO: remove in version 2.0.0
     *
     * @var string|null The color theme of the reCAPTCHA widget
     */
    public $reCaptchaTheme;

    /**
     * TODO: remove in version 2.0.0
     *
     * @var string|null The position of the reCAPTCHA badge (when invisible)
     */
    public $reCaptchaBadge;

    /**
     * @var int The maximum number of pending contacts to store per email address and mailing list
     */
    public $maxPendingContacts = 5;

    /**
     * @var mixed The amount of time to wait before purging pending contacts in seconds or as an interval (0 for disabled)
     */
    public $purgePendingContactsDuration = 0;

    /**
     * @var array Extra fields and the operators that should be available to segments
     * @since 1.7.3
     */
    public $extraSegmentFieldOperators = [];

    // Public Methods
    // =========================================================================

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
            'contactFieldLayout' => [
                'class' => FieldLayoutBehavior::class,
                'elementType' => ContactElement::class,
                'idAttribute' => 'contactFieldLayoutId',
            ],
        ];
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            [['apiKey', 'fromNamesEmails', 'transportType', 'emailFieldLabel', 'maxBatchSize', 'memoryLimit', 'timeLimit'], 'required'],
            [['apiKey'], 'string', 'length' => [16]],
            [['fromNamesEmails'], 'validateFromNamesEmails'],
            [['contactFieldLayoutId', 'maxBatchSize', 'timeLimit'], 'integer'],
            [['ipstackApiKey'], 'required', 'when' => function($model) {
                return $model->geoIp;
            }],
            [['reCaptchaSiteKey', 'reCaptchaSecretKey', 'reCaptchaErrorMessage'], 'required', 'when' => function($model) {
                return $model->reCaptcha;
            }],
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
     *
     * @return FieldLayout
     */
    public function getContactFieldLayout(): FieldLayout
    {
        return Craft::$app->getFields()->getLayoutByType(ContactElement::class);
    }

    /**
     * Returns the contact fields.
     *
     * @return FieldInterface[]
     */
    public function getContactFields(): array
    {
        return $this->getContactFieldLayout()->getFields();
    }

    /**
     * Validates the from names and emails
     *
     * @param mixed $attribute
     */
    public function validateFromNamesEmails($attribute)
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
}
