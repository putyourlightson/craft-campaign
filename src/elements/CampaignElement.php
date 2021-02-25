<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\elements;

use craft\elements\actions\Restore;
use craft\web\View;
use DateTime;
use Exception;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\db\CampaignElementQuery;
use putyourlightson\campaign\helpers\NumberHelper;
use putyourlightson\campaign\records\CampaignRecord;
use putyourlightson\campaign\models\CampaignTypeModel;

use Craft;
use craft\base\Element;
use craft\elements\db\ElementQueryInterface;
use craft\elements\actions\Edit;
use craft\elements\actions\Delete;
use craft\helpers\UrlHelper;
use craft\validators\DateTimeValidator;
use yii\base\InvalidConfigException;

/**
 * CampaignElement
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 *
 * @property CampaignTypeModel $campaignType
 * @property float $clickThroughRate
 * @property string $reportUrl
 */
class CampaignElement extends Element
{
    // Constants
    // =========================================================================

    const STATUS_SENT = 'sent';
    const STATUS_PENDING = 'pending';
    const STATUS_CLOSED = 'closed';

    // Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('campaign', 'Campaign');
    }

    /**
     * @inheritdoc
     */
    public static function refHandle()
    {
        return 'campaign';
    }

    /**
     * @inheritdoc
     */
    public static function hasContent(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function hasTitles(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function hasUris(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function isLocalized(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function hasStatuses(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_SENT => Craft::t('campaign', 'Sent'),
            self::STATUS_PENDING => Craft::t('campaign', 'Pending'),
            self::STATUS_CLOSED => Craft::t('campaign', 'Closed'),
            self::STATUS_DISABLED => Craft::t('app', 'Disabled')
        ];
    }

    /**
     * @return CampaignElementQuery
     */
    public static function find(): ElementQueryInterface
    {
        return new CampaignElementQuery(static::class);
    }

    /**
     * @inheritdoc
     */
    protected static function defineSources(string $context = null): array
    {
        $sources = [
            [
                'key' => '*',
                'label' => Craft::t('campaign', 'All campaigns'),
                'criteria' => [],
                'defaultSort' => ['lastSent', 'desc'],
            ]
        ];

        $sources[] = ['heading' => Craft::t('campaign', 'Campaign Types')];

        $campaignTypes = Campaign::$plugin->campaignTypes->getAllCampaignTypes();

        foreach ($campaignTypes as $campaignType) {
            /** @var CampaignTypeModel $campaignType */
            $sources[] = [
                'key' => 'campaignType:'.$campaignType->id,
                'label' => $campaignType->name,
                'sites' => [$campaignType->siteId],
                'data' => [
                    'handle' => $campaignType->handle
                ],
                'criteria' => [
                    'campaignTypeId' => $campaignType->id
                ],
                'defaultSort' => ['lastSent', 'desc'],
            ];
        }

        return $sources;
    }

    /**
     * @inheritdoc
     */
    protected static function defineActions(string $source = null): array
    {
        $actions = [];

        $elementsService = Craft::$app->getElements();

        // Edit
        $actions[] = $elementsService->createAction([
            'type' => Edit::class,
            'label' => Craft::t('campaign', 'Edit campaign'),
        ]);

        // Delete
        $actions[] = $elementsService->createAction([
            'type' => Delete::class,
            'confirmationMessage' => Craft::t('campaign', 'Are you sure you want to delete the selected campaigns?'),
            'successMessage' => Craft::t('campaign', 'Campaigns deleted.'),
        ]);

        // Restore
        $actions[] = $elementsService->createAction([
            'type' => Restore::class,
            'successMessage' => Craft::t('campaign', 'Campaigns restored.'),
            'partialSuccessMessage' => Craft::t('campaign', 'Some campaigns restored.'),
            'failMessage' => Craft::t('campaign', 'Campaigns not restored.'),
        ]);

        return $actions;
    }

    /**
     * @inheritdoc
     */
    protected static function defineSortOptions(): array
    {
        return [
            'title' => Craft::t('app', 'Title'),
            'uri' => Craft::t('app', 'URI'),
            'recipients' => Craft::t('campaign', 'Recipients'),
            'opened' => Craft::t('campaign', 'Opened'),
            'clicked' => Craft::t('campaign', 'Clicked'),
            'opens' => Craft::t('campaign', 'Opens'),
            'clicks' => Craft::t('campaign', 'Clicks'),
            'unsubscribed' => Craft::t('campaign', 'Unsubscribed'),
            'complained' => Craft::t('campaign', 'Complained'),
            'bounced' => Craft::t('campaign', 'Bounced'),
            'lastSent' => Craft::t('campaign', 'Last Sent'),
            [
                'label' => Craft::t('app', 'Date Created'),
                'orderBy' => 'elements.dateCreated',
                'attribute' => 'dateCreated'
            ],
            [
                'label' => Craft::t('app', 'Date Updated'),
                'orderBy' => 'elements.dateUpdated',
                'attribute' => 'dateUpdated'
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    protected static function defineTableAttributes(): array
    {
        return [
            'title' => ['label' => Craft::t('app', 'Title')],
            'campaignType' => ['label' => Craft::t('campaign', 'Campaign Type')],
            'recipients' => ['label' => Craft::t('campaign', 'Recipients')],
            'opened' => ['label' => Craft::t('campaign', 'Opened')],
            'clicked' => ['label' => Craft::t('campaign', 'Clicked')],
            'opens' => ['label' => Craft::t('campaign', 'Opens')],
            'clicks' => ['label' => Craft::t('campaign', 'Clicks')],
            'unsubscribed' => ['label' => Craft::t('campaign', 'Unsubscribed')],
            'complained' => ['label' => Craft::t('campaign', 'Complained')],
            'bounced' => ['label' => Craft::t('campaign', 'Bounced')],
            'lastSent' => ['label' => Craft::t('campaign', 'Last Sent')],
            'slug' => ['label' => Craft::t('app', 'Slug')],
            'uri' => ['label' => Craft::t('app', 'URI')],
            'link' => ['label' => Craft::t('app', 'Link'), 'icon' => 'world'],
            'id' => ['label' => Craft::t('app', 'ID')],
            'dateCreated' => ['label' => Craft::t('app', 'Date Created')],
            'dateUpdated' => ['label' => Craft::t('app', 'Date Updated')],
        ];
    }

    /**
     * @inheritdoc
     */
    protected static function defineDefaultTableAttributes(string $source): array
    {
        $attributes = [];

        if ($source === '*') {
            $attributes[] = 'campaignType';
        }

        array_push($attributes, 'recipients', 'opened', 'clicked', 'link');

        return $attributes;
    }

    // Properties
    // =========================================================================

    /**
     * @var int|null Campaign type ID
     */
    public $campaignTypeId;

    /**
     * @var int Recipients
     */
    public $recipients = 0;

    /**
     * @var int Opened
     */
    public $opened = 0;

    /**
     * @var int Clicked
     */
    public $clicked = 0;

    /**
     * @var int Opens
     */
    public $opens = 0;

    /**
     * @var int Clicks
     */
    public $clicks = 0;

    /**
     * @var int Unsubscribed
     */
    public $unsubscribed = 0;

    /**
     * @var int Complained
     */
    public $complained = 0;

    /**
     * @var int Bounced
     */
    public $bounced = 0;

    /**
     * @var DateTime|null Date closed
     */
    public $dateClosed;

    /**
     * @var DateTime|null Last sent
     */
    public $lastSent;

    /**
     * @var CampaignTypeModel Campaign type
     */
    private $_campaignType;

    /**
     * @var string
     */
    private $_language;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function datetimeAttributes(): array
    {
        $names = parent::datetimeAttributes();
        $names[] = 'dateClosed';
        $names[] = 'lastSent';

        return $names;
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        $rules = parent::rules();

        $rules[] = [['campaignTypeId', 'recipients', 'opened', 'clicked', 'opens', 'clicks', 'unsubscribed', 'complained', 'bounced'], 'integer'];
        $rules[] = [['dateClosed', 'lastSent'], DateTimeValidator::class];

        return $rules;
    }

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function getSupportedSites(): array
    {
        return [$this->getCampaignType()->siteId];
    }

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function getUriFormat()
    {
        return $this->getCampaignType()->uriFormat;
    }

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    protected function route()
    {
        return [
            'templates/render', [
                'template' => $this->getCampaignType()->htmlTemplate,
                'variables' => [
                    'campaign' => $this,
                    'browserVersionUrl' => $this->url,
                    'contact' => new ContactElement(),
                    'unsubscribeUrl' => '',
                ]
            ]
        ];
    }

    /**
     * Returns the campaign's campaign type
     *
     * @return CampaignTypeModel
     * @throws InvalidConfigException
     */
    public function getCampaignType(): CampaignTypeModel
    {
        if ($this->_campaignType !== null) {
            return $this->_campaignType;
        }

        if ($this->campaignTypeId === null) {
            throw new InvalidConfigException('Campaign is missing its campaign type ID');
        }

        $campaignType = Campaign::$plugin->campaignTypes->getCampaignTypeById($this->campaignTypeId);

        if ($campaignType === null) {
            throw new InvalidConfigException('Invalid campaign type ID: '.$this->campaignTypeId);
        }

        $this->_campaignType = $campaignType;

        return $campaignType;
    }

    /**
     * Returns the campaign's HTML body
     *
     * @param ContactElement|null $contact
     * @param SendoutElement|null $sendout
     *
     * @return string
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function getHtmlBody(ContactElement $contact = null, SendoutElement $sendout = null): string
    {
        return $this->_getBody('html', $contact, $sendout);
    }

    /**
     * Returns the campaign's plaintext body
     *
     * @param ContactElement|null $contact
     * @param SendoutElement|null $sendout
     *
     * @return string
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function getPlaintextBody(ContactElement $contact = null, SendoutElement $sendout = null): string
    {
        return html_entity_decode($this->_getBody('plaintext', $contact, $sendout), ENT_QUOTES);
    }

    /**
     * Returns the campaign's rate
     *
     * @param string $field
     *
     * @return int
     */
    public function getRate(string $field): int
    {
        return $this->recipients > 0 ? NumberHelper::floorOrOne(($this->{$field} / $this->recipients) * 100) : 0;
    }

    /**
     * Returns the campaign's click-through rate
     *
     * @return int
     */
    public function getClickThroughRate(): int
    {
        return $this->opened > 0 ? NumberHelper::floorOrOne(($this->clicked / $this->opened) * 100) : 0;
    }

    /**
     * @inheritdoc
     */
    public function getStatus()
    {
        if (!$this->enabled || !$this->enabledForSite) {
            return self::STATUS_DISABLED;
        }

        if ($this->dateClosed) {
            return self::STATUS_CLOSED;
        }

        if ($this->recipients) {
            return self::STATUS_SENT;
        }

        return self::STATUS_PENDING;
    }

    /**
     * @inheritdoc
     */
    public function getIsEditable(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function getCpEditUrl()
    {
        return UrlHelper::cpUrl('campaign/campaigns/'.$this->getCampaignType()->handle.'/'.$this->id);
    }

    /**
     * Returns the campaign's report URL
     *
     * @return string
     */
    public function getReportUrl(): string
    {
        return UrlHelper::cpUrl('campaign/reports/campaigns/'.$this->id);
    }

    // Indexes, etc.
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    protected function tableAttributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'campaignType':
                return $this->getCampaignType()->name;
            case 'clickThroughRate':
                return $this->getClickThroughRate().'%';
        }

        return parent::tableAttributeHtml($attribute);
    }

    /**
     * @inheritdoc
     * @return string
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function getEditorHtml(): string
    {
        // Get the title field
        $html = Craft::$app->getView()->renderTemplate('campaign/campaigns/_includes/titlefield', [
            'campaign' => $this
        ]);

        // Set the field layout ID
        $this->fieldLayoutId = $this->getCampaignType()->fieldLayoutId;

        $html .= parent::getEditorHtml();

        return $html;
    }

    // Events
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public function afterSave(bool $isNew)
    {
        if ($isNew) {
            $campaignRecord = new CampaignRecord();
            $campaignRecord->id = $this->id;
        }
        else {
            $campaignRecord = CampaignRecord::findOne($this->id);
        }

        if ($campaignRecord) {
            // Set attributes
            $campaignRecord->campaignTypeId = $this->campaignTypeId;
            $campaignRecord->recipients = $this->recipients;
            $campaignRecord->opened = $this->opened;
            $campaignRecord->clicked = $this->clicked;
            $campaignRecord->opens = $this->opens;
            $campaignRecord->clicks = $this->clicks;
            $campaignRecord->unsubscribed = $this->unsubscribed;
            $campaignRecord->complained = $this->complained;
            $campaignRecord->bounced = $this->bounced;
            $campaignRecord->dateClosed = $this->dateClosed;

            $campaignRecord->save(false);
        }

        parent::afterSave($isNew);
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns the campaign's body
     *
     * @param string|null $templateType
     * @param ContactElement|null $contact
     * @param SendoutElement|null $sendout
     *
     * @return string
     * @throws InvalidConfigException
     * @throws Exception
     */
    private function _getBody(string $templateType = null, ContactElement $contact = null, SendoutElement $sendout = null): string
    {
        $templateType = $templateType ?? 'html';

        if ($contact === null) {
            $contact = new ContactElement();
        }

        if ($sendout === null) {
            $sendout = new SendoutElement();
        }

        $view = Craft::$app->getView();

        // Get template mode so we can reset later
        $templateMode = $view->getTemplateMode();

        // Set template mode to site if different
        if ($templateMode !== View::TEMPLATE_MODE_SITE) {
            $view->setTemplateMode(View::TEMPLATE_MODE_SITE);
        }

        // Get body from rendered template with variables
        $template = $templateType == 'html' ? $this->getCampaignType()->htmlTemplate : $this->getCampaignType()->plaintextTemplate;

        // Set the current site from the campaign's site ID
        Craft::$app->getSites()->setCurrentSite($this->siteId);

        // Set the language to the campaign's language as this does not automatically happen for CP requests
        Craft::$app->language = $this->_getLanguage();

        try {
            $body = $view->renderTemplate($template, [
                'campaign' => $this,
                'browserVersionUrl' => $this->url,
                'contact' => $contact,
                'sendout' => $sendout,
                'unsubscribeUrl' => $contact->getUnsubscribeUrl($sendout),
            ]);
        }
        catch (Exception $e) {
            Campaign::$plugin->log($e->getMessage());
            throw $e;
        }

        // Reset template mode if different
        if ($templateMode !== View::TEMPLATE_MODE_SITE) {
            $view->setTemplateMode($templateMode);
        }

        return $body;
    }

    /**
     * Returns the campaign's language
     *
     * @return string
     */
    private function _getLanguage(): string
    {
        if ($this->_language === null) {
            $this->_language = $this->getSite()->language;
        }

        return $this->_language;
    }
}
