<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\models;

use Craft;
use craft\base\Model;
use craft\behaviors\FieldLayoutBehavior;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\models\Site;

use craft\validators\HandleValidator;
use craft\validators\SiteIdValidator;
use craft\validators\UniqueValidator;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\records\MailingListTypeRecord;

/**
 * @mixin FieldLayoutBehavior
 *
 * @property-read null|Site $site
 * @property-read string $cpEditUrl
 *
 * @method FieldLayout getFieldLayout()
 * @method setFieldLayout(FieldLayout $fieldLayout)
 */
class MailingListTypeModel extends Model
{
    /**
     * @var int|null ID
     */
    public ?int $id;

    /**
     * @var int|null Site ID
     */
    public ?int $siteId;

    /**
     * @var int|null Field layout ID
     */
    public ?int $fieldLayoutId;

    /**
     * @var string|null Name
     */
    public ?string $name;

    /**
     * @var string|null Handle
     */
    public ?string $handle;

    /**
     * @var bool Subscribe verification required
     */
    public bool $subscribeVerificationRequired = true;

    /**
     * @var string|null Subscribe verification email subject
     */
    public ?string $subscribeVerificationEmailSubject;

    /**
     * @var string|null Subscribe verification email template
     */
    public ?string $subscribeVerificationEmailTemplate;

    /**
     * @var string|null Subscribe verification success template
     */
    public ?string $subscribeVerificationSuccessTemplate;

    /**
     * @var string|null Subscribe success template
     */
    public ?string $subscribeSuccessTemplate;

    /**
     * @var bool Unsubscribe form allowed
     */
    public bool $unsubscribeFormAllowed = false;

    /**
     * @var string|null Unsubscribe verification email subject
     */
    public ?string $unsubscribeVerificationEmailSubject;

    /**
     * @var string|null Unsubscribe verification email template
     */
    public ?string $unsubscribeVerificationEmailTemplate;

    /**
     * @var string|null Unsubscribe success template
     */
    public ?string $unsubscribeSuccessTemplate;

    /**
     * @var string|null UID
     */
    public ?string $uid;

    /**
     * Returns the handle as the string representation.
     */
    public function __toString(): string
    {
        return (string)$this->handle;
    }

    /**
     * @inheritdoc
     */
    protected function defineBehaviors(): array
    {
        return [
            'fieldLayout' => [
                'class' => FieldLayoutBehavior::class,
                'elementType' => MailingListElement::class,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        return [
            [['id', 'siteId', 'fieldLayoutId'], 'integer'],
            [['siteId'], SiteIdValidator::class],
            [['siteId', 'name', 'handle'], 'required'],
            [['name', 'handle'], 'string', 'max' => 255],
            [['name', 'handle'], UniqueValidator::class, 'targetClass' => MailingListTypeRecord::class],
            [['handle'], HandleValidator::class, 'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title']],
            [['subscribeVerificationRequired', 'unsubscribeFormAllowed'], 'boolean'],
        ];
    }

    /**
     * Returns the CP edit URL.
     */
    public function getCpEditUrl(): string
    {
        return UrlHelper::cpUrl('campaign/settings/mailinglisttypes/' . $this->id);
    }

    /**
     * Returns the site.
     */
    public function getSite(): ?Site
    {
        if ($this->siteId === null) {
            return Craft::$app->getSites()->getPrimarySite();
        }

        return Craft::$app->getSites()->getSiteById($this->siteId);
    }
}
