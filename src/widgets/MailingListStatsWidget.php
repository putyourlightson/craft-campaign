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
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\helpers\DateRangeHelper;
use putyourlightson\campaign\records\ContactMailingListRecord;

/**
 * @property-read string|null $title
 * @property-read string|null $subtitle
 * @property-read string|null $bodyHtml
 * @property-read string|null $settingsHtml
 *
 * @since 2.4.0
 */
class MailingListStatsWidget extends Widget
{
    use DateRangeWidgetTrait;

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('campaign', 'Mailing List Stats');
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
    public ?int $mailingListTypeId = null;

    /**
     * @var array
     */
    public array $visibility = [
        'mailingLists' => true,
        'subscribed' => true,
        'unsubscribed' => true,
        'complained' => true,
        'bounced' => true,
    ];

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

        $mailingListQuery = MailingListElement::find();
        $contactMailingListQuery = ContactMailingListRecord::find();

        if ($this->mailingListTypeId) {
            $mailingListQuery->mailingListTypeId($this->mailingListTypeId);
            $contactMailingListQuery->innerJoinWith('mailingList')
                ->andWhere(['mailingListTypeId' => $this->mailingListTypeId]);
        }

        if ($this->dateRange) {
            [$startDate, $endDate] = DateRangeHelper::dateRangeByType($this->dateRange);
            $startDate = Db::prepareDateForDb($startDate);
            $endDate = Db::prepareDateForDb($endDate);

            $mailingListQuery->andWhere(['and',
                ['>=', 'dateCreated', $startDate],
                ['<', 'dateCreated', $endDate],
            ]);
            $contactMailingListQuery->andWhere(['and',
                ['>=', 'subscribed', $startDate],
                ['<', 'subscribed', $endDate],
            ]);
        }

        $mailingLists = $mailingListQuery->count();
        $subscribed = $contactMailingListQuery->andWhere(['subscriptionStatus' => 'subscribed'])->count();
        $unsubscribed = $contactMailingListQuery->andWhere(['subscriptionStatus' => 'unsubscribed'])->count();
        $complained = $contactMailingListQuery->andWhere(['subscriptionStatus' => 'complained'])->count();
        $bounced = $contactMailingListQuery->andWhere(['subscriptionStatus' => 'bounced'])->count();

        return Craft::$app->getView()->renderTemplate('campaign/_widgets/mailing-list-stats/widget', [
            'visibility' => $this->visibility,
            'mailingLists' => $mailingLists,
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
