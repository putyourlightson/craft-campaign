<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\models;

use putyourlightson\campaign\base\BaseModel;
use putyourlightson\campaign\elements\CampaignElement;
use putyourlightson\campaign\records\CampaignTypeRecord;

use Craft;
use craft\behaviors\FieldLayoutBehavior;
use craft\helpers\UrlHelper;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;

/**
 * CampaignTypeModel
 *
 * @mixin FieldLayoutBehavior
 * 
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 *
 * @property string $cpEditUrl
 */
class CampaignTypeModel extends BaseModel
{
    // Properties
    // =========================================================================

    /**
     * @var int|null ID
     */
    public $id;

    /**
     * @var int|null Field layout ID
     */
    public $fieldLayoutId;

    /**
     * @var string|null Name
     */
    public $name;

    /**
     * @var string|null Handle
     */
    public $handle;

    // Public Methods
    // =========================================================================

    /**
     * Use the handle as the string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->handle;
    }

    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {
        return [
            'fieldLayout' => [
                'class' => FieldLayoutBehavior::class,
                'elementType' => CampaignElement::class
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['id', 'fieldLayoutId'], 'integer'],
            [['name', 'handle'], 'required'],
            [['name', 'handle'], 'string', 'max' => 255],
            [
                ['handle'],
                HandleValidator::class,
                'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title']
            ],
            [
                ['name'],
                UniqueValidator::class,
                'targetClass' => CampaignTypeRecord::class,
                'targetAttribute' => ['name'],
                'comboNotUnique' => Craft::t('yii', '{attribute} "{value}" has already been taken.'),
            ],
            [
                ['handle'],
                UniqueValidator::class,
                'targetClass' => CampaignTypeRecord::class,
                'targetAttribute' => ['handle'],
                'comboNotUnique' => Craft::t('yii', '{attribute} "{value}" has already been taken.'),
            ],
        ];
    }

    /**
     * Returns the CP edit URL.
     *
     * @return string
     */
    public function getCpEditUrl(): string
    {
        return UrlHelper::cpUrl('campaign/settings/campaigntypes/'.$this->id);
    }
}
