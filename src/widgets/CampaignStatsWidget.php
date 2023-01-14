<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\widgets;

use Craft;
use craft\base\Widget;
use craft\helpers\Db;
use putyourlightson\campaign\assets\WidgetAsset;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\CampaignElement;
use putyourlightson\campaign\elements\SendoutElement;
use putyourlightson\campaign\helpers\DateRangeHelper;
use putyourlightson\campaign\helpers\NumberHelper;
use putyourlightson\campaign\records\ContactCampaignRecord;

/**
 * @property-read string|null $title
 * @property-read string|null $subtitle
 * @property-read string|null $bodyHtml
 * @property-read string|null $settingsHtml
 *
 * @since 2.4.0
 */
class CampaignStatsWidget extends Widget
{
    use DateRangeWidgetTrait;

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('campaign', 'Campaign Stats');
    }

    /**
     * @inheritdoc
     */
    public static function icon(): ?string
    {
        return Craft::getAlias('@putyourlightson/campaign/icon-mask.svg');
    }

    /**
     * @var int|null
     */
    public ?int $campaignTypeId = null;

    /**
     * @var array
     */
    public array $visibility = [
        'campaigns' => true,
        'sendouts' => true,
        'recipients' => true,
        'openRate' => true,
        'clickRate' => true,
    ];

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

        $campaignQuery = CampaignElement::find();
        $sendoutQuery = SendoutElement::find();
        $contactCampaignQuery = ContactCampaignRecord::find();

        if ($this->campaignTypeId) {
            $campaignQuery->campaignTypeId($this->campaignTypeId);
            $campaignIds = $campaignQuery->ids();
            $sendoutQuery->andWhere(['campaignId' => $campaignIds]);
            $contactCampaignQuery->andWhere(['campaignId' => $campaignIds]);
        }

        if ($this->dateRange) {
            [$startDate, $endDate] = DateRangeHelper::dateRangeByType($this->dateRange);
            $startDate = Db::prepareDateForDb($startDate);
            $endDate = Db::prepareDateForDb($endDate);

            $campaignQuery->andWhere(['and',
                ['>=', 'dateCreated', $startDate],
                ['<', 'dateCreated', $endDate],
            ]);
            $sendoutQuery->andWhere(['and',
                ['>=', 'sendDate', $startDate],
                ['<', 'sendDate', $endDate],
            ]);
            $contactCampaignQuery->andWhere(['and',
                ['>=', 'sent', $startDate],
                ['<', 'sent', $endDate],
            ]);
        }

        $campaigns = $campaignQuery->count();
        $sendouts = $sendoutQuery->count();
        $recipients = (clone $contactCampaignQuery)->count();
        $opened = (clone $contactCampaignQuery)->andWhere(['not', ['opened' => null]])->count();
        $clicked = (clone $contactCampaignQuery)->andWhere(['not', ['clicked' => null]])->count();

        return Craft::$app->getView()->renderTemplate('campaign/_widgets/campaign-stats/widget', [
            'visibility' => $this->visibility,
            'campaigns' => $campaigns,
            'sendouts' => $sendouts,
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
