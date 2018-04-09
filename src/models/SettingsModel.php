<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\models;

use putyourlightson\campaign\elements\ContactElement;

use craft\base\Model;
use craft\behaviors\FieldLayoutBehavior;

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
     * @var string|null The default name to send emails from
     */
    public $defaultFromName;

    /**
     * @var string|null The default email address to send emails from
     */
    public $defaultFromEmail;

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
     * @var int The max size of sendout batches
     */
    public $maxBatchSize = 1000;

    /**
     * @var int The max number of sendout retry attempts
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
            [['apiKey', 'defaultFromName', 'defaultFromEmail', 'transportType', 'emailFieldLabel'], 'required'],
            [['apiKey'], 'string', 'length' => [16]],
            [['defaultFromName', 'defaultFromEmail'], 'string', 'max' => 255],
            [['contactFieldLayoutId'], 'integer']
        ];
    }
}
