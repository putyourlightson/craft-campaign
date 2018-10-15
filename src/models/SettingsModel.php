<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\models;

use putyourlightson\campaign\elements\ContactElement;

use Craft;
use craft\base\Model;
use craft\behaviors\FieldLayoutBehavior;
use yii\validators\EmailValidator;

/**
 * SettingsModel
 *
 * @mixin FieldLayoutBehavior
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
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
     * @var mixed|null The from names and emails that sendouts can be sent from
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
     * @var int|null Contact field layout ID
     */
    public $contactFieldLayoutId;

    /**
     * @var bool Enable GeoIP to geolocate contacts by their IP addresses
     */
    public $geoIp = false;

    /**
     * @var string The ipstack.com API key
     */
    public $ipstackApiKey;

    /**
     * @var bool Enable reCAPTCHA to protect mailing list subscription forms from bots
     */
    public $reCaptcha = false;

    /**
     * @var string The reCAPTCHA site key
     */
    public $reCaptchaSiteKey;

    /**
     * @var string The reCAPTCHA secret key
     */
    public $reCaptchaSecretKey;

    /**
     * @var string The reCAPTCHA error message
     */
    public $reCaptchaErrorMessage = 'Your form submission was blocked. Please go back and verify that you are human.';

    /**
     * @var string The size of the reCAPTCHA widget
     */
    public $reCaptchaSize;

    /**
     * @var string The color theme of the reCAPTCHA widget
     */
    public $reCaptchaTheme;

    /**
     * @var string The position of the reCAPTCHA badge (when invisible)
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
     * @var float The threshold for memory usage per sendout batch as a fraction
     */
    public $memoryThreshold = 0.8;

    /**
     * @var float The threshold for execution time per sendout batch as a fraction
     */
    public $timeThreshold = 0.8;

    /**
     * @var mixed The memory usage limit per sendout batch in bytes or a shorthand byte value (set to -1 for unlimited)
     */
    public $memoryLimit = '1024M';

    /**
     * @var int The execution time limit per sendout batch in seconds (set to 0 for unlimited)
     */
    public $timeLimit = 300;

    /**
     * @var int The maximum size of sendout batches
     */
    public $maxBatchSize = 1000;

    /**
     * @var int The maximum number of sendout retry attempts
     */
    public $maxRetryAttempts = 10;

    /**
     * @var int The amount of time in seconds to delay jobs between sendout batches
     */
    public $batchJobDelay = 10;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {
        return [
            'contactFieldLayout' => [
                'class' => FieldLayoutBehavior::class,
                'elementType' => ContactElement::class,
                'idAttribute' => 'contactFieldLayoutId'
            ],
        ];
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            [['fromNamesEmails', 'transportType', 'emailFieldLabel'], 'required'],
            [['apiKey'], 'string', 'length' => [16]],
            [['fromNamesEmails'], 'validateFromNamesEmails'],
            [['contactFieldLayoutId'], 'integer'],
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
        $labels['fromNamesEmails'] = Craft::t('campaign', 'From Names and Emails');
        $labels['reCaptchaSiteKey'] = Craft::t('campaign', 'reCAPTCHA Site Key');
        $labels['reCaptchaSecretKey'] = Craft::t('campaign', 'reCAPTCHA Secret Key');
        $labels['reCaptchaErrorMessage'] = Craft::t('campaign', 'reCAPTCHA Error Message');

        return $labels;
    }

    /**
     * Validates the from names and emails
     *
     * @param $attribute
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
        }
    }
}
