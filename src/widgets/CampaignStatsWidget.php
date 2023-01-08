<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\widgets;

use Craft;
use craft\base\Widget;
use craft\helpers\DateRange;
use craft\helpers\Db;
use putyourlightson\campaign\assets\WidgetAsset;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\SendoutElement;
use putyourlightson\campaign\helpers\NumberHelper;
use putyourlightson\campaign\records\CampaignRecord;
use putyourlightson\campaign\records\ContactCampaignRecord;

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
        $contactCampaignQuery = ContactCampaignRecord::find();
        $campaignIdQuery = CampaignRecord::find()->select('id');

        if ($this->campaignTypeId) {
            $campaignIdQuery->andWhere(['campaignTypeId' => $this->campaignTypeId]);
            $campaignIds = $campaignIdQuery->column();
            $sendoutQuery->andWhere(['campaignId' => $campaignIds]);
            $contactCampaignQuery->andWhere(['campaignId' => $campaignIds]);
        }

        if ($this->dateRange) {
            [$startDate, $endDate] = DateRange::dateRangeByType($this->dateRange);
            $startDate = Db::prepareDateForDb($startDate);
            $endDate = Db::prepareDateForDb($endDate);

            $sendoutQuery->andWhere(['and',
                ['>=', 'sendDate', $startDate],
                ['<', 'sendDate', $endDate],
            ]);
            $contactCampaignQuery->andWhere(['and',
                ['>=', 'sent', $startDate],
                ['<', 'sent', $endDate],
            ]);
        }

        $sendouts = $sendoutQuery->count();
        $recipients = $contactCampaignQuery->count();
        $opened = $contactCampaignQuery->andWhere(['not', ['opened' => null]])->count();
        $clicked = $contactCampaignQuery->andWhere(['not', ['clicked' => null]])->count();

        return Craft::$app->getView()->renderTemplate('campaign/_widgets/campaign-stats/widget', [
            'settings' => $this->getSettings(),
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
