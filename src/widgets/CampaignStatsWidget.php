<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\widgets;

use Craft;
use craft\base\Widget;
use putyourlightson\campaign\assets\WidgetAsset;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\CampaignElement;
use putyourlightson\campaign\elements\SendoutElement;
use putyourlightson\campaign\helpers\NumberHelper;

/**
 * @property-read string|null $title
 * @property-read string|null $subtitle
 * @property-read string|null $bodyHtml
 * @property-read string|null $settingsHtml
 */
class CampaignStatsWidget extends Widget
{
    use WidgetTrait;

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('campaign', 'Campaign Stats');
    }

    /**
     * @var int|null
     */
    public ?int $campaignTypeId = null;

    /**
     * @var bool
     */
    public bool $showSendouts = true;

    /**
     * @var bool
     */
    public bool $showRecipients = true;

    /**
     * @var bool
     */
    public bool $showOpenRate = true;

    /**
     * @var bool
     */
    public bool $showClickRate = true;

    /**
     * @inheritdoc
     */
    public function getTitle(): ?string
    {
        if ($this->campaignTypeId) {
            $campaignType = Campaign::$plugin->campaignTypes->getCampaignTypeById($this->campaignTypeId);

            if ($campaignType) {
                return Craft::t('campaign', '“{name}” Campaigns', ['name' => $campaignType->name]);
            }
        }

        return Craft::t('campaign', 'All Campaigns');
    }

    /**
     * @inheritdoc
     */
    public function getSubtitle(): ?string
    {
        $dateRangeOptions = $this->getDateRangeOptions();

        return $dateRangeOptions[$this->dateRange];
    }

    /**
     * @inheritdoc
     */
    public function getBodyHtml(): ?string
    {
        Craft::$app->getView()->registerAssetBundle(WidgetAsset::class);

        $sendoutQuery = SendoutElement::find();
        $campaignQuery = CampaignElement::find()
            ->status(CampaignElement::STATUS_SENT);

        if ($this->campaignTypeId) {
            $campaignIds = CampaignElement::find()
                ->campaignTypeId($this->campaignTypeId)
                ->ids();
            $sendoutQuery->andWhere(['campaignId' => $campaignIds]);
            $campaignQuery->campaignTypeId($this->campaignTypeId);
        }

        $campaigns = $campaignQuery->all();
        $recipients = 0;
        $opened = 0;
        $clicked = 0;

        /** @var CampaignElement $campaign */
        foreach ($campaigns as $campaign) {
            $recipients += $campaign->recipients;
            $opened += $campaign->opened;
            $clicked += $campaign->clicked;
        }

        return Craft::$app->getView()->renderTemplate('campaign/_widgets/campaign-stats/widget', [
            'settings' => $this->getSettings(),
            'sendouts' => $sendoutQuery->count(),
            'recipients' => $recipients,
            'openRate' => $recipients ? NumberHelper::floorOrOne($opened / $recipients * 100) : 0,
            'clickRate' => $opened ? NumberHelper::floorOrOne($clicked / $opened * 100) : 0,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        $campaignTypeOptions = [null => Craft::t('campaign', 'All')];
        $campaignTypes = Campaign::$plugin->campaignTypes->getAllCampaignTypes();

        foreach ($campaignTypes as $campaignType) {
            $campaignTypeOptions[$campaignType->id] = $campaignType->name;
        }

        return Craft::$app->getView()->renderTemplate('campaign/_widgets/campaign-stats/settings', [
            'widget' => $this,
            'dateRangeOptions' => $this->getDateRangeOptions(),
            'campaignTypeOptions' => $campaignTypeOptions,
        ]);
    }
}
