<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\widgets;

use Craft;
use craft\base\conditions\BaseDateRangeConditionRule;
use craft\base\Widget;
use craft\helpers\DateRange;
use craft\helpers\Db;
use putyourlightson\campaign\assets\WidgetAsset;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\records\ContactMailingListRecord;

/**
 * @property-read string|null $title
 * @property-read string|null $bodyHtml
 * @property-read string|null $settingsHtml
 * @property-read array $dateRangeOptions
 */
class MailingListSubscribersWidget extends Widget
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('campaign', 'Mailing List Subscribers');
    }

    /**
     * @inheritdoc
     */
    public static function icon(): ?string
    {
        return Craft::getAlias('@putyourlightson/campaign/icon-mask.svg');
    }

    /**
     * @var string|null
     */
    public ?string $dateRange = null;

    /**
     * @var array|null
     */
    public ?array $mailingListIds = null;

    /**
     * @inheritdoc
     */
    public function getTitle(): ?string
    {
        return '';
    }

    /**
     * @inheritdoc
     */
    public function getBodyHtml(): ?string
    {
        Craft::$app->getView()->registerAssetBundle(WidgetAsset::class);

        $query = ContactMailingListRecord::find()
            ->where(['subscriptionStatus' => 'subscribed']);

        if (!empty($this->mailingListIds)) {
            $query->andWhere(['mailingListId' => $this->mailingListIds]);
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

        $subscribers = $query->count();

        return Craft::$app->getView()->renderTemplate('campaign/_widgets/subscribers/widget', [
            'widget' => $this,
            'subscribers' => $subscribers,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('campaign/_widgets/subscribers/settings', [
            'widget' => $this,
            'dateRangeOptions' => $this->getDateRangeOptions(),
            'mailingListElementType' => MailingListElement::class,
            'mailingLists' => Campaign::$plugin->mailingLists->getMailingListsByIds($this->mailingListIds),
        ]);
    }

    /**
     * @see BaseDateRangeConditionRule::rangeTypeOptions()
     */
    public function getDateRangeOptions(): array
    {
        return [
            null => Craft::t('campaign', 'All'),
            DateRange::TYPE_TODAY => Craft::t('app', 'Today'),
            DateRange::TYPE_THIS_WEEK => Craft::t('app', 'This week'),
            DateRange::TYPE_THIS_MONTH => Craft::t('app', 'This month'),
            DateRange::TYPE_THIS_YEAR => Craft::t('app', 'This year'),
            DateRange::TYPE_PAST_7_DAYS => Craft::t('app', 'Past {num} days', ['num' => 7]),
            DateRange::TYPE_PAST_30_DAYS => Craft::t('app', 'Past {num} days', ['num' => 30]),
            DateRange::TYPE_PAST_90_DAYS => Craft::t('app', 'Past {num} days', ['num' => 90]),
            DateRange::TYPE_PAST_YEAR => Craft::t('app', 'Past year'),
        ];
    }
}
