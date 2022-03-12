<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\elements;

use Craft;
use craft\base\Element;
use craft\elements\actions\Delete;
use craft\elements\actions\Edit;
use craft\elements\actions\Restore;
use craft\elements\actions\View as ViewAction;
use craft\elements\User;
use craft\helpers\Cp;
use craft\helpers\Html;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\validators\DateTimeValidator;
use craft\web\View;
use DateTime;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\db\CampaignElementQuery;
use putyourlightson\campaign\fieldlayoutelements\reports\CampaignReportFieldLayoutTab;
use putyourlightson\campaign\helpers\NumberHelper;
use putyourlightson\campaign\models\CampaignTypeModel;
use putyourlightson\campaign\records\CampaignRecord;
use Twig\Error\Error;
use yii\base\InvalidConfigException;
use yii\i18n\Formatter;

/**
 * @property-read CampaignTypeModel $campaignType
 * @property-read bool $isEditable
 * @property-read int $clickThroughRate
 * @property-read string[] $cacheTags
 * @property-read null|string $postEditUrl
 * @property-read array[] $crumbs
 * @property-read string $reportUrl
 */
class CampaignElement extends Element
{
    /**
     * @const string
     */
    public const STATUS_SENT = 'sent';

    /**
     * @const string
     */
    public const STATUS_PENDING = 'pending';

    /**
     * @const string
     */
    public const STATUS_CLOSED = 'closed';

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
    public static function lowerDisplayName(): string
    {
        return Craft::t('campaign', 'campaign');
    }

    /**
     * @inheritdoc
     */
    public static function pluralDisplayName(): string
    {
        return Craft::t('campaign', 'Campaigns');
    }

    /**
     * @inheritdoc
     */
    public static function pluralLowerDisplayName(): string
    {
        return Craft::t('campaign', 'campaigns');
    }

    /**
     * @inheritdoc
     */
    public static function refHandle(): ?string
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
            self::STATUS_DISABLED => Craft::t('app', 'Disabled'),
        ];
    }

    /**
     * @inheritdoc
     */
    public static function find(): CampaignElementQuery
    {
        return new CampaignElementQuery(static::class);
    }

    /**
     * @inheritdoc
     * @param CampaignTypeModel $context
     * @since 2.0.0
     */
    public static function gqlTypeNameByContext(mixed $context): string
    {
        return $context->handle . '_CampaignType';
    }

    /**
     * @inheritdoc
     * @param CampaignTypeModel $context
     * @since 2.0.0
     */
    public static function gqlScopesByContext(mixed $context): array
    {
        return ['campaigntypes.' . $context->uid];
    }

    /**
     * @inheritdoc
     * @param CampaignTypeModel $context
     * @since 2.0.0
     */
    public static function gqlMutationNameByContext(mixed $context): string
    {
        return 'save_' . self::gqlTypeNameByContext($context);
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
            ],
            [
                'heading' => Craft::t('campaign', 'Campaign Types'),
            ],
        ];

        $campaignTypes = Campaign::$plugin->campaignTypes->getAllCampaignTypes();

        foreach ($campaignTypes as $campaignType) {
            $sources[] = [
                'key' => 'campaignType:' . $campaignType->uid,
                'label' => $campaignType->name,
                'sites' => [$campaignType->siteId],
                'data' => ['handle' => $campaignType->handle],
                'criteria' => ['campaignTypeId' => $campaignType->id],
                'defaultSort' => ['lastSent', 'desc'],
            ];
        }

        return $sources;
    }

    /**
     * @inheritdoc
     * @since 2.0.0
     */
    protected static function defineFieldLayouts(string $source): array
    {
        $fieldLayouts = [];

        if (preg_match('/^campaignType:(.+)$/', $source, $matches)) {
            $campaignType = Campaign::$plugin->campaignTypes->getCampaignTypeByUid($matches[1]);

            if ($campaignType) {
                $fieldLayouts[] = $campaignType->getFieldLayout();
            }
        }

        return $fieldLayouts;
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

        // View
        $actions[] = $elementsService->createAction([
            'type' => ViewAction::class,
            'label' => Craft::t('campaign', 'View campaign'),
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
                'attribute' => 'dateCreated',
            ],
            [
                'label' => Craft::t('app', 'Date Updated'),
                'orderBy' => 'elements.dateUpdated',
                'attribute' => 'dateUpdated',
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

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['campaignTypeId', 'recipients', 'opened', 'clicked', 'opens', 'clicks', 'unsubscribed', 'complained', 'bounced'], 'number', 'integerOnly' => true];
        $rules[] = [['dateClosed', 'lastSent'], DateTimeValidator::class];

        return $rules;
    }

    /**
     * @inheritdoc
     */
    protected function tableAttributeHtml(string $attribute): string
    {
        return match ($attribute) {
            'campaignType' => $this->getCampaignType()->name,
            'clickThroughRate' => $this->getClickThroughRate() . '%',
            default => parent::tableAttributeHtml($attribute),
        };
    }

    /**
     * @inheritdoc
     * @since 2.0.0
     */
    public function getCacheTags(): array
    {
        return [
            "campaignType:$this->campaignTypeId",
        ];
    }

    /**
     * @var int|null Campaign type ID
     */
    public ?int $campaignTypeId = null;

    /**
     * @var int Recipients
     */
    public int $recipients = 0;

    /**
     * @var int Opened
     */
    public int $opened = 0;

    /**
     * @var int Clicked
     */
    public int $clicked = 0;

    /**
     * @var int Opens
     */
    public int $opens = 0;

    /**
     * @var int Clicks
     */
    public int $clicks = 0;

    /**
     * @var int Unsubscribed
     */
    public int $unsubscribed = 0;

    /**
     * @var int Complained
     */
    public int $complained = 0;

    /**
     * @var int Bounced
     */
    public int $bounced = 0;

    /**
     * @var DateTime|null Date closed
     */
    public ?DateTime $dateClosed = null;

    /**
     * @var DateTime|null Last sent
     */
    public ?DateTime $lastSent = null;

    /**
     * @var null|CampaignTypeModel Campaign type
     */
    private ?CampaignTypeModel $_campaignType = null;

    /**
     * @var null|string
     */
    private ?string $_language = null;

    /**
     * @inheritdoc
     */
    public function getSupportedSites(): array
    {
        return [$this->getCampaignType()->siteId];
    }

    /**
     * @inheritdoc
     */
    public function getUriFormat(): ?string
    {
        return $this->getCampaignType()->uriFormat;
    }

    /**
     * @inheritdoc
     */
    protected function uiLabel(): ?string
    {
        if (!isset($this->title) || trim($this->title) === '') {
            return Craft::t('campaign', 'Untitled campaign');
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    protected function previewTargets(): array
    {
        return [
            [
                'label' => Craft::t('app', 'Primary {type} page', [
                    'type' => self::lowerDisplayName(),
                ]),
                'url' => $this->getUrl(),
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    protected function route(): array|string|null
    {
        return [
            'templates/render', [
                'template' => $this->getCampaignType()->htmlTemplate,
                'variables' => [
                    'campaign' => $this,
                    'browserVersionUrl' => $this->url,
                    'contact' => new ContactElement(),
                    'unsubscribeUrl' => '',
                    'isWebRequest' => true,
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     * @since 2.0.0
     */
    public function canView(User $user): bool
    {
        if (parent::canView($user)) {
            return true;
        }

        return $user->can('campaign:campaigns');
    }

    /**
     * @inheritdoc
     * @since 2.0.0
     */
    public function canSave(User $user): bool
    {
        if (parent::canView($user)) {
            return true;
        }

        return $user->can('campaign:campaigns');
    }


    /**
     * @inheritdoc
     * @since 2.0.0
     */
    public function canDuplicate(User $user): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     * @since 2.0.0
     */
    public function canDelete(User $user): bool
    {
        if (parent::canView($user)) {
            return true;
        }

        return $user->can('campaign:campaigns');
    }

    /**
     * @inheritdoc
     * @since 2.0.0
     */
    public function canCreateDrafts(User $user): bool
    {
        return true;
    }

    /**
     * Returns the campaign's campaign type.
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
            throw new InvalidConfigException('Invalid campaign type ID: ' . $this->campaignTypeId);
        }

        $this->_campaignType = $campaignType;

        return $campaignType;
    }

    /**
     * Returns the campaign's HTML body.
     */
    public function getHtmlBody(ContactElement $contact = null, SendoutElement $sendout = null, MailingListElement $mailingList = null): string
    {
        return $this->_getBody('html', $contact, $sendout, $mailingList);
    }

    /**
     * Returns the campaign's plaintext body.
     */
    public function getPlaintextBody(ContactElement $contact = null, SendoutElement $sendout = null, MailingListElement $mailingList = null): string
    {
        return html_entity_decode($this->_getBody('plaintext', $contact, $sendout, $mailingList), ENT_QUOTES);
    }

    /**
     * Returns the campaign's rate.
     */
    public function getRate(string $field): int
    {
        return $this->recipients > 0 ? NumberHelper::floorOrOne(($this->{$field} / $this->recipients) * 100) : 0;
    }

    /**
     * Returns the campaign's click-through rate.
     */
    public function getClickThroughRate(): int
    {
        return $this->opened > 0 ? NumberHelper::floorOrOne(($this->clicked / $this->opened) * 100) : 0;
    }

    /**
     * @inheritdoc
     */
    public function getStatus(): ?string
    {
        if (!$this->enabled) { // || !$this->enabledForSite) {
            return self::STATUS_DISABLED;
        }

        if ($this->dateClosed !== null) {
            return self::STATUS_CLOSED;
        }

        if ($this->recipients > 0) {
            return self::STATUS_SENT;
        }

        return self::STATUS_PENDING;
    }

    /**
     * @inheritdoc
     * @since 2.0.0
     */
    public function hasRevisions(): bool
    {
        return $this->getCampaignType()->enableVersioning;
    }

    /**
     * @inheritdoc
     * @since 2.0.0
     */
    protected function cpEditUrl(): ?string
    {
        $campaignType = $this->getCampaignType();

        $path = sprintf('campaign/campaigns/%s/%s', $campaignType->handle, $this->getCanonicalId());

        // Ignore homepage/temp slugs
        if ($this->slug && !str_starts_with($this->slug, '__')) {
            $path .= "-$this->slug";
        }

        return UrlHelper::cpUrl($path);
    }

    /**
     * Returns the campaign's report URL
     */
    public function getReportUrl(): string
    {
        return UrlHelper::cpUrl('campaign/reports/campaigns/' . $this->id);
    }

    /**
     * @inheritdoc
     */
    public function getPostEditUrl(): ?string
    {
        $campaignType = $this->getCampaignType();

        return UrlHelper::cpUrl("campaign/campaigns/$campaignType->handle");
    }

    /**
     * @inheritdoc
     * @since 2.0.0
     */
    public function getCrumbs(): array
    {
        $campaignType = $this->getCampaignType();

        return [
            [
                'label' => Craft::t('campaign', 'Campaigns'),
                'url' => UrlHelper::url('campaign/campaigns'),
            ],
            [
                'label' => Craft::t('campaign', $campaignType->name),
                'url' => UrlHelper::url('campaign/campaigns/' . $campaignType->handle),
            ],
        ];
    }

    /**
     * @inheritdoc
     * @since 2.0.0
     */
    public function getFieldLayout(): ?FieldLayout
    {
        $fieldLayout = parent::getFieldLayout() ?? $this->getCampaignType()->getFieldLayout();
        $fieldLayout->setTabs(array_merge(
            $fieldLayout->getTabs(),
            [
                new CampaignReportFieldLayoutTab(),
            ],
        ));

        return $fieldLayout;
    }

    /**
     * @inheritdoc
     * @since 2.0.0
     */
    protected function metadata(): array
    {
        $metadata = parent::metadata();
        $formatter = Craft::$app->getFormatter();

        if ($this->lastSent) {
            $metadata[Craft::t('campaign', 'Last sent')] = $formatter->asDatetime($this->lastSent, Formatter::FORMAT_WIDTH_SHORT);
        }

        if ($this->dateClosed) {
            $metadata[Craft::t('campaign', 'Closed at')] = $formatter->asDatetime($this->dateClosed, Formatter::FORMAT_WIDTH_SHORT);
        }

        return $metadata;
    }

    /**
     * @inheritdoc
     * @since 2.0.0
     */
    protected function metaFieldsHtml(bool $static): string
    {
        $testEmailFieldHtml = Cp::elementSelectFieldHtml([
            'label' => Craft::t('campaign', 'Test Email'),
            'fieldClass' => 'test-email',
            'id' => 'testContacts',
            'name' => 'testContacts',
            'elementType' => ContactElement::class,
            'selectionLabel' => Craft::t('campaign', 'Add a contact'),
            'criteria' => [
                'status' => ContactElement::STATUS_ACTIVE,
            ],
            'elements' => $this->getCampaignType()->getTestContacts(),
        ]);
        $testEmailFieldHtml .= Html::a(
            Craft::t('campaign', 'Send Test'),
            '',
            [
                'class' => 'send-test btn',
            ]
        );

        return implode('', [
            $this->slugFieldHtml($static),
            $testEmailFieldHtml,
            parent::metaFieldsHtml($static),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function afterSave(bool $isNew): void
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

    /**
     * Returns the campaign's body
     */
    private function _getBody(string $templateType = null, ContactElement $contact = null, SendoutElement $sendout = null, MailingListElement $mailingList = null): string
    {
        $templateType = $templateType ?? 'html';

        if ($contact === null) {
            $contact = new ContactElement();
        }

        if ($sendout === null) {
            $sendout = new SendoutElement();
        }

        if ($mailingList === null) {
            $mailingList = new MailingListElement();
        }

        // Get body from rendered template with variables
        $template = $templateType == 'html' ? $this->getCampaignType()->htmlTemplate : $this->getCampaignType()->plaintextTemplate;

        // Set the current site from the campaign's site ID
        Craft::$app->getSites()->setCurrentSite($this->siteId);

        // Set the language to the campaign's language as this does not automatically happen for CP requests
        Craft::$app->language = $this->_getLanguage();

        try {
            $body = Craft::$app->getView()->renderTemplate(
                $template,
                [
                    'campaign' => $this,
                    'browserVersionUrl' => $this->url,
                    'contact' => $contact,
                    'sendout' => $sendout,
                    'mailingList' => $mailingList,
                    'unsubscribeUrl' => $contact->getUnsubscribeUrl($sendout),
                    'isWebRequest' => false,
                ],
                View::TEMPLATE_MODE_SITE,
            );
        }
        catch (Error $exception) {
            Campaign::$plugin->log($exception->getMessage());

            throw $exception;
        }

        return $body;
    }

    /**
     * Returns the campaign's language
     */
    private function _getLanguage(): string
    {
        if ($this->_language === null) {
            $this->_language = $this->getSite()->language;
        }

        return $this->_language;
    }
}
