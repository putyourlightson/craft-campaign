<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\elements;

use Craft;
use craft\base\Element;
use craft\elements\actions\Delete;
use craft\elements\actions\Duplicate;
use craft\elements\actions\Edit;
use craft\elements\actions\Restore;
use craft\elements\Entry;
use craft\elements\User;
use craft\helpers\Cp;
use craft\helpers\Html;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\validators\DateTimeValidator;
use craft\web\CpScreenResponseBehavior;
use craft\web\View;
use DateTime;
use putyourlightson\campaign\assets\SendTestAsset;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\actions\ViewCampaign;
use putyourlightson\campaign\elements\db\CampaignElementQuery;
use putyourlightson\campaign\fieldlayoutelements\reports\CampaignReportFieldLayoutTab;
use putyourlightson\campaign\helpers\NumberHelper;
use putyourlightson\campaign\models\CampaignTypeModel;
use putyourlightson\campaign\records\CampaignRecord;
use Twig\Error\Error;
use yii\base\InvalidConfigException;
use yii\i18n\Formatter;
use yii\web\Response;

/**
 * @property-read CampaignTypeModel $campaignType
 * @property-read int $openRate
 * @property-read int $clickRate
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
    public static function trackChanges(): bool
    {
        return true;
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
    protected static function defineSources(string $context): array
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
            'type' => ViewCampaign::class,
        ]);

        // Duplicate
        $actions[] = $elementsService->createAction([
            'type' => Duplicate::class,
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
            'openRate' => ['label' => Craft::t('campaign', 'Open Rate')],
            'clickRate' => ['label' => Craft::t('campaign', 'Click Rate')],
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

        $attributes[] = 'recipients';
        $attributes[] = 'opened';
        $attributes[] = 'clicked';
        $attributes[] = 'lastSent';
        $attributes[] = 'link';

        return $attributes;
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
     * @var null|FieldLayout Field layout
     */
    private ?FieldLayout $_fieldLayout = null;

    /**
     * @var null|string
     */
    private ?string $_language = null;

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['campaignTypeId'], 'required'];
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
            'openRate' => $this->getOpenRate() . '%',
            'clickRate' => $this->getClickRate() . '%',
            default => parent::tableAttributeHtml($attribute),
        };
    }

    /**
     * @inheritdoc
     * @since 2.0.0
     * TODO: replace with cacheTags() in version 3.0.0
     */
    public function getCacheTags(): array
    {
        return [
            "campaignType:$this->campaignTypeId",
        ];
    }

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
            ],
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

        return $this->_canManage($user);
    }

    /**
     * @inheritdoc
     * @since 2.0.0
     */
    public function canSave(User $user): bool
    {
        if (parent::canSave($user)) {
            return true;
        }

        return $this->_canManage($user);
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
        if (parent::canDelete($user)) {
            return true;
        }

        return $this->_canManage($user);
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
        Campaign::$plugin->campaigns->prepareRequestToGetHtmlBody();

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
     * Returns the campaign's open rate.
     */
    public function getOpenRate(): int
    {
        return $this->recipients > 0 ? NumberHelper::floorOrOne(($this->opened / $this->recipients) * 100) : 0;
    }

    /**
     * Returns the campaign's click rate.
     */
    public function getClickRate(): int
    {
        return $this->opened > 0 ? NumberHelper::floorOrOne(($this->clicked / $this->opened) * 100) : 0;
    }

    /**
     * Returns the campaign's report URL
     */
    public function getReportUrl(string $suffix = null, array $params = null): string
    {
        $url = 'campaign/reports/campaigns/' . $this->id;

        if ($suffix) {
            $url .= '/' . trim($suffix, '/');
        }

        return UrlHelper::cpUrl($url, $params);
    }

    /**
     * @inheritdoc
     */
    public function getStatus(): ?string
    {
        if (!$this->enabled || $this->getIsDraft()) {
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
        return true;
    }

    /**
     * @inheritdoc
     * @since 2.0.0
     */
    public function prepareEditScreen(Response $response, string $containerId): void
    {
        /** @var Response|CpScreenResponseBehavior $response */
        $response->selectedSubnavItem = 'campaigns';

        $campaignType = $this->getCampaignType();
        $response->crumbs([
            [
                'label' => Craft::t('campaign', 'Campaigns'),
                'url' => UrlHelper::url('campaign/campaigns'),
            ],
            [
                'label' => Craft::t('campaign', $campaignType->name),
                'url' => UrlHelper::url('campaign/campaigns/' . $campaignType->handle),
            ],
        ]);

        $response->addAltAction(
            Craft::t('campaign', 'Save and create new regular sendout'),
            [
                'redirect' => 'campaign/sendouts/regular/new?campaignId=' . $this->id,
            ],
        );
        $response->addAltAction(
            Craft::t('campaign', 'Save and create new scheduled sendout'),
            [
                'redirect' => 'campaign/sendouts/scheduled/new?campaignId=' . $this->id,
            ],
        );

        if ($this->getStatus() == CampaignElement::STATUS_SENT) {
            $response->addAltAction(
                Craft::t('campaign', 'Close campaign'),
                [
                    'action' => 'campaign/campaigns/close',
                    'confirm' => Craft::t('campaign', 'Are you sure you want to close this campaign? This will remove all contact activity related to this campaign. This action cannot be undone.'),
                ],
            );
        }
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
    public function getFieldLayout(): ?FieldLayout
    {
        // Memoize the field layout to ensure we don't end up with duplicate extra tabs!
        if ($this->_fieldLayout !== null) {
            return $this->_fieldLayout;
        }

        $this->_fieldLayout = parent::getFieldLayout() ?? $this->getCampaignType()->getFieldLayout();

        if (!Craft::$app->getRequest()->getIsCpRequest()) {
            return $this->_fieldLayout;
        }

        if ($this->getStatus() == CampaignElement::STATUS_SENT) {
            $this->_fieldLayout->setTabs(array_merge(
                $this->_fieldLayout->getTabs(),
                [
                    new CampaignReportFieldLayoutTab(),
                ],
            ));
        }

        return $this->_fieldLayout;
    }

    /**
     * @inheritdoc
     * @since 2.0.0
     */
    protected function metadata(): array
    {
        $formatter = Craft::$app->getFormatter();
        $metadata = parent::metadata();

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
        return $this->slugFieldHtml($static) . parent::metaFieldsHtml($static);
    }

    /**
     * @inheritdoc
     */
    protected function statusFieldHtml(): string
    {
        Craft::$app->getView()->registerAssetBundle(SendTestAsset::class);

        $testEmailField = Cp::elementSelectFieldHtml([
            'id' => 'testContacts',
            'name' => 'testContacts',
            'elementType' => ContactElement::class,
            'selectionLabel' => Craft::t('campaign', 'Add a contact'),
            'criteria' => ['status' => ContactElement::STATUS_ACTIVE],
            'elements' => $this->getCampaignType()->getTestContacts(),
        ]);

        $sendTestButton = Cp::fieldHtml(
            Html::button(Craft::t('campaign', 'Send Test'), [
                'data' => [
                    'action' => UrlHelper::actionUrl('campaign/campaigns/send-test'),
                    'campaign' => $this->id,
                ],
                'class' => 'send-test btn',
            ])
        );

        $testEmailFieldHtml = Html::beginTag('fieldset') .
            Html::tag('legend', Craft::t('campaign', 'Test Email'), ['class' => 'h6']) .
            Html::tag('div', $testEmailField . $sendTestButton, ['class' => 'meta test-email']) .
            Html::endTag('fieldset');

        return parent::statusFieldHtml() . $testEmailFieldHtml;
    }

    /**
     * @inheritdoc
     */
    public function beforeSave(bool $isNew): bool
    {
        // Reset stats if this is a duplicate of a non-draft campaign.
        if ($this->firstSave && $this->duplicateOf !== null) {
            $this->recipients = 0;
            $this->opened = 0;
            $this->clicked = 0;
            $this->opens = 0;
            $this->clicks = 0;
            $this->unsubscribed = 0;
            $this->complained = 0;
            $this->bounced = 0;
            $this->dateClosed = null;
        }

        $this->_updateTitle();

        return parent::beforeSave($isNew);
    }

    /**
     * @inheritdoc
     */
    public function afterSave(bool $isNew): void
    {
        if (!$this->propagating) {
            if ($isNew) {
                $campaignRecord = new CampaignRecord();
                $campaignRecord->id = $this->id;
            } else {
                $campaignRecord = CampaignRecord::findOne($this->id);
            }

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
     * @inheritdoc
     */
    public function afterPropagate(bool $isNew): void
    {
        parent::afterPropagate($isNew);

        // Save a new revision?
        if ($this->_shouldSaveRevision()) {
            Craft::$app->getRevisions()->createRevision($this, $this->revisionCreatorId, $this->revisionNotes);
        }
    }

    /**
     * Returns whether the campaign should be saving revisions on save.
     *
     * @see Entry::_shouldSaveRevision()
     */
    private function _shouldSaveRevision(): bool
    {
        return (
            $this->id &&
            !$this->propagating &&
            !$this->resaving &&
            !$this->getIsDraft() &&
            !$this->getIsRevision()
        );
    }

    /**
     * Updates the campaign’s title, if its campaign type has a dynamic title format.
     *
     * @since 2.5.0
     * @see Entry::updateTitle()
     */
    private function _updateTitle(): void
    {
        $campaignType = $this->getCampaignType();

        if ($campaignType->hasTitleField === false) {
            // Make sure that the locale has been loaded in case the title format has any Date/Time fields
            Craft::$app->getLocale();
            // Set Craft to the campaign’s site’s language, in case the title format has any static translations
            $language = Craft::$app->language;
            Craft::$app->language = $this->getSite()->language;
            $title = Craft::$app->getView()->renderObjectTemplate($campaignType->titleFormat, $this);
            if ($title !== '') {
                $this->title = $title;
            }
            Craft::$app->language = $language;
        }
    }

    /**
     * Returns the campaign's body
     */
    private function _getBody(string $templateType = 'html', ContactElement $contact = null, SendoutElement $sendout = null, MailingListElement $mailingList = null): string
    {
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
        $variables = [
            'campaign' => $this,
            'browserVersionUrl' => $this->url,
            'contact' => $contact,
            'sendout' => $sendout,
            'mailingList' => $mailingList,
            'unsubscribeUrl' => $contact->getUnsubscribeUrl($sendout),
            'isWebRequest' => false,
        ];

        // Set the current site from the campaign's site ID
        Craft::$app->getSites()->setCurrentSite($this->siteId);

        // Set the language to the campaign's language as this does not automatically happen for CP requests
        Craft::$app->language = $this->_getLanguage();

        try {
            // Render the page template only for HTML, to prevent Yii block tags being left behind
            if ($templateType == 'html') {
                $body = Craft::$app->getView()->renderPageTemplate($template, $variables, View::TEMPLATE_MODE_SITE);
            } else {
                $body = Craft::$app->getView()->renderTemplate($template, $variables, View::TEMPLATE_MODE_SITE);
            }
        } catch (Error $exception) {
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

    /**
     * Returns whether the campaign can be managed by the user.
     */
    private function _canManage(User $user): bool
    {
        return $user->can('campaign:campaigns:' . $this->getCampaignType()->uid);
    }
}
