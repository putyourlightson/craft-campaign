<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\models;

use Craft;
use craft\behaviors\FieldLayoutBehavior;
use craft\helpers\UrlHelper;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;
use craft\models\Site;
use craft\validators\SiteIdValidator;
use craft\validators\UriFormatValidator;
use putyourlightson\campaign\base\BaseModel;
use putyourlightson\campaign\elements\CampaignElement;
use putyourlightson\campaign\records\CampaignTypeRecord;

/**
 * CampaignTypeModel
 *
 * @mixin FieldLayoutBehavior
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 *
 * @property Site|null $site
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
     * @var int|null Site ID
     */
    public $siteId;

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
     * @var string|null URI format
     */
    public $uriFormat;

    /**
     * @var string|null HTML template
     */
    public $htmlTemplate;

    /**
     * @var string|null Plaintext template
     */
    public $plaintextTemplate;

    /**
     * @var string|null Query string parameters
     */
    public $queryStringParameters;

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
            [['id', 'siteId', 'fieldLayoutId'], 'integer'],
            [['siteId'], SiteIdValidator::class],
            [['siteId', 'name', 'handle', 'uriFormat', 'htmlTemplate', 'plaintextTemplate'], 'required'],
            [['name', 'handle'], 'string', 'max' => 255],
            [['name', 'handle'], UniqueValidator::class, 'targetClass' => CampaignTypeRecord::class],
            [['handle'], HandleValidator::class, 'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title']],
            [['uriFormat'], UriFormatValidator::class],
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

    /**
     * Returns the site.
     *
     * @return Site|null
     */
    public function getSite()
    {
        if ($this->siteId === null) {
            return Craft::$app->getSites()->getPrimarySite();
        }

        return Craft::$app->getSites()->getSiteById($this->siteId);
    }

    /**
     * Returns whether the URI format is set and if the template paths are valid.
     *
     * @return bool
     */
    public function hasValidTemplates(): bool
    {
        if ($this->uriFormat === null) {
            return false;
        }

        // Set Craft to the site template mode
        $view = Craft::$app->getView();
        $oldTemplateMode = $view->getTemplateMode();
        $view->setTemplateMode($view::TEMPLATE_MODE_SITE);

        // Do the templates exist?
        $templatesExist = $this->htmlTemplate !== null && $view->doesTemplateExist((string)$this->htmlTemplate) && $this->plaintextTemplate !== null && $view->doesTemplateExist((string)$this->plaintextTemplate);

        // Restore the original template mode
        $view->setTemplateMode($oldTemplateMode);

        return $templatesExist;
    }
}
