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
use putyourlightson\campaign\records\ContactMailingListRecord;

/**
 * @property-read string|null $title
 * @property-read string|null $subtitle
 * @property-read string|null $bodyHtml
 * @property-read string|null $settingsHtml
 */
class MailingListStatsWidget extends Widget
{
    use WidgetTrait;

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('campaign', 'Mailing List Stats');
    }

    /**
     * @var int|null
     */
    public ?int $mailingListTypeId = null;

    /**
     * @var bool
     */
    public bool $showSubscribed = true;

    /**
     * @var bool
     */
    public bool $showUnsubscribed = true;

    /**
     * @var bool
     */
    public bool $showComplained = true;

    /**
     * @var bool
     */
    public bool $showBounced = true;

    /**
     * @inheritdoc
     */
    public function getTitle(): ?string
    {
        if ($this->mailingListTypeId) {
            $mailingListType = Campaign::$plugin->mailingListTypes->getMailingListTypeById($this->mailingListTypeId);

            if ($mailingListType) {
                return Craft::t('campaign', '“{name}” Mailing Lists', ['name' => $mailingListType->name]);
            }
        }

        return Craft::t('campaign', 'All Mailing Lists');
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

        $query = ContactMailingListRecord::find();

        if ($this->mailingListTypeId) {
            $query->innerJoinWith('mailingList')
                ->andWhere(['mailingListTypeId' => $this->mailingListTypeId]);
        }

        if ($this->dateRange) {
            [$startDate, $endDate] = DateRange::dateRangeByType($this->dateRange);
            $startDate = Db::prepareDateForDb($startDate);
            $endDate = Db::prepareDateForDb($endDate);
            $query->andWhere(['and',
                ['>=', 'subscribed', $startDate],
                ['<', 'subscribed', $endDate],
            ]);
        }

        $subscribed = $query->where(['subscriptionStatus' => 'subscribed'])->count();
        $unsubscribed = $query->where(['subscriptionStatus' => 'unsubscribed'])->count();
        $complained = $query->where(['subscriptionStatus' => 'complained'])->count();
        $bounced = $query->where(['subscriptionStatus' => 'bounced'])->count();

        return Craft::$app->getView()->renderTemplate('campaign/_widgets/mailing-list-stats/widget', [
            'settings' => $this->getSettings(),
            'subscribed' => $subscribed,
            'unsubscribed' => $unsubscribed,
            'complained' => $complained,
            'bounced' => $bounced,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        $mailingListTypeOptions = [null => Craft::t('campaign', 'All')];
        $mailingListTypes = Campaign::$plugin->mailingListTypes->getAllMailingListTypes();

        foreach ($mailingListTypes as $mailingListType) {
            $mailingListTypeOptions[$mailingListType->id] = $mailingListType->name;
        }

        return Craft::$app->getView()->renderTemplate('campaign/_widgets/mailing-list-stats/settings', [
            'widget' => $this,
            'dateRangeOptions' => $this->getDateRangeOptions(),
            'mailingListTypeOptions' => $mailingListTypeOptions,
        ]);
    }
}
