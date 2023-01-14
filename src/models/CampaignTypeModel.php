<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\models;

use Craft;
use craft\base\Model;
use craft\behaviors\FieldLayoutBehavior;
use craft\elements\User;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\models\Site;
use craft\validators\HandleValidator;
use craft\validators\SiteIdValidator;
use craft\validators\UniqueValidator;
use craft\validators\UriFormatValidator;
use craft\web\View;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\CampaignElement;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\records\CampaignTypeRecord;

/**
 * @mixin FieldLayoutBehavior
 *
 * @property-read null|Site $site
 * @property-read string $cpEditUrl
 * @property-read ContactElement[] $testContacts
 * @property-read ContactElement[] $testContactsWithDefault
 */
class CampaignTypeModel extends Model
{
    /**
     * @var int|null ID
     */
    public ?int $id = null;

    /**
     * @var int|null Site ID
     */
    public ?int $siteId = null;

    /**
     * @var int|null Field layout ID
     */
    public ?int $fieldLayoutId = null;

    /**
     * @var string|null Name
     */
    public ?string $name = null;

    /**
     * @var string|null Handle
     */
    public ?string $handle = null;

    /**
     * @var string|null URI format
     */
    public ?string $uriFormat = null;

    /**
     * @var string|null HTML template
     */
    public ?string $htmlTemplate = null;

    /**
     * @var string|null Plaintext template
     */
    public ?string $plaintextTemplate = null;

    /**
     * @var string|null Query string parameters
     */
    public ?string $queryStringParameters = null;

    /**
     * @var int[]|string|null
     */
    public array|string|null $testContactIds = null;

    /**
     * @var bool Has title field
     * @since 2.5.0
     */
    public bool $hasTitleField = true;

    /**
     * @var string|null Title format
     * @since 2.5.0
     */
    public ?string $titleFormat = null;

    /**
     * @var string|null UID
     */
    public ?string $uid = null;

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
                'elementType' => CampaignElement::class,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['id', 'siteId', 'fieldLayoutId'], 'integer'];
        $rules[] = [['siteId'], SiteIdValidator::class];
        $rules[] = [['siteId', 'name', 'handle', 'uriFormat', 'htmlTemplate', 'plaintextTemplate'], 'required'];
        $rules[] = [['name', 'handle'], 'string', 'max' => 255];
        $rules[] = [['name', 'handle'], UniqueValidator::class, 'targetClass' => CampaignTypeRecord::class];
        $rules[] = [['handle'], HandleValidator::class, 'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title']];
        $rules[] = [['uriFormat'], UriFormatValidator::class];

        if (!$this->hasTitleField) {
            $rules[] = [['titleFormat'], 'required'];
        }

        return $rules;
    }

    /**
     * Returns the CP edit URL.
     */
    public function getCpEditUrl(): string
    {
        return UrlHelper::cpUrl('campaign/settings/campaigntypes/' . $this->id);
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

    /**
     * Returns the test contacts.
     *
     * @return ContactElement[]
     */
    public function getTestContacts(): array
    {
        if (empty($this->testContactIds)) {
            return [];
        }

        /** @var int[] $testContactIds */
        $testContactIds = Json::decodeIfJson($this->testContactIds);
        $testContacts = [];

        // Loop over test contact IDs to ensure their order is maintained
        foreach ($testContactIds as $testContactId) {
            $testContact = Campaign::$plugin->contacts->getContactById($testContactId);

            if ($testContact !== null) {
                $testContacts[] = $testContact;
            }
        }

        return $testContacts;
    }

    /**
     * Returns the test contacts with the default if empty.
     *
     * @return ContactElement[]
     */
    public function getTestContactsWithDefault(): array
    {
        $testContacts = $this->getTestContacts();

        if (empty($testContacts)) {
            /** @var User|null $currentUser */
            $currentUser = Craft::$app->getUser()->getIdentity();

            if ($currentUser !== null) {
                $contact = Campaign::$plugin->contacts->getContactByEmail(
                    $currentUser->email
                );

                if ($contact !== null) {
                    $testContacts = [$contact];
                }
            }
        }

        return $testContacts;
    }

    /**
     * Returns whether the URI format is set and if the template paths are valid.
     */
    public function hasValidTemplates(): bool
    {
        if ($this->uriFormat === null) {
            return false;
        }

        // Return whether both templates exist.
        return $this->htmlTemplate !== null && $this->plaintextTemplate !== null
            && Craft::$app->getView()->doesTemplateExist($this->htmlTemplate, View::TEMPLATE_MODE_SITE)
            && Craft::$app->getView()->doesTemplateExist($this->plaintextTemplate, View::TEMPLATE_MODE_SITE);
    }
}
