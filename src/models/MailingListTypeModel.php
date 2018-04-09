<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\models;

use putyourlightson\campaign\base\BaseModel;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\records\MailingListTypeRecord;

use Craft;
use craft\behaviors\FieldLayoutBehavior;
use craft\helpers\UrlHelper;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;

/**
 * MailingListTypeModel
 *
 * @mixin FieldLayoutBehavior
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 *
 * @property string $cpEditUrl
 */
class MailingListTypeModel extends BaseModel
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

    /**
     * @var bool Double opt-in
     */
    public $doubleOptIn = true;

    /**
     * @var bool Require MLID
     */
    public $requireMlid = true;

    /**
     * @var string|null Subscribe success template
     */
    public $subscribeSuccessTemplate;

    /**
     * @var string|null Unsubscribe success template
     */
    public $unsubscribeSuccessTemplate;

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
                'elementType' => MailingListElement::class
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
            [['name', 'handle', 'subscribeSuccessTemplate', 'unsubscribeSuccessTemplate'], 'string', 'max' => 255],
            [['doubleOptIn', 'requireMlid'], 'boolean'],
            [
                ['handle'],
                HandleValidator::class,
                'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title']
            ],
            [
                ['name'],
                UniqueValidator::class,
                'targetClass' => MailingListTypeRecord::class,
                'targetAttribute' => ['name'],
                'comboNotUnique' => Craft::t('yii', '{attribute} "{value}" has already been taken.'),
            ],
            [
                ['handle'],
                UniqueValidator::class,
                'targetClass' => MailingListTypeRecord::class,
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
        return UrlHelper::cpUrl('campaign/settings/mailinglisttypes/'.$this->id);
    }
}
