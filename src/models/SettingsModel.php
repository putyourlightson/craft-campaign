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
     * @var bool Test mode
     */
    public $testMode = false;

    /**
     * @var string API Key
     */
    public $apiKey;

    /**
     * @var string|null Default from name
     */
    public $defaultFromName;

    /**
     * @var string|null Default from email
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
     * @var string Email field label
     */
    public $emailFieldLabel = 'Email';

    /**
     * @var int|null Contact field layout ID
     */
    public $contactFieldLayoutId;

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
            [['contactFieldLayoutId'], 'number', 'integerOnly' => true]
        ];
    }
}
