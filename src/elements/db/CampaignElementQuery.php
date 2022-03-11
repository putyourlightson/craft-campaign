<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\elements\db;

use craft\db\Table;
use putyourlightson\campaign\elements\CampaignElement;
use putyourlightson\campaign\models\CampaignTypeModel;
use putyourlightson\campaign\records\CampaignTypeRecord;

use craft\elements\db\ElementQuery;
use craft\helpers\Db;
use putyourlightson\campaign\records\SendoutRecord;
use yii\db\Connection;

/**
 * @method CampaignElement[]|array all($db = null)
 * @method CampaignElement|array|null one($db = null)
 * @method CampaignElement|array|null nth(int $n, Connection $db = null)
 */
class CampaignElementQuery extends ElementQuery
{
    /**
     * @var int|int[]|null The campaign type ID(s) that the resulting campaigns must have.
     */
    public array|int|null $campaignTypeId;

    /**
     * @inheritdoc
     */
    public function __set($name, $value)
    {
        switch ($name) {
            case 'campaignType':
                $this->campaignType($value);
                break;
            default:
                parent::__set($name, $value);
        }
    }

    /**
     * Sets the [[campaignType]] property.
     */
    public function campaignType(array|CampaignTypeModel|string|null $value): static
    {
        if ($value instanceof CampaignTypeModel) {
            $this->campaignTypeId = $value->id;
        }
        elseif ($value !== null) {
            $this->campaignTypeId = CampaignTypeRecord::find()
                ->select(['id'])
                ->where(Db::parseParam('handle', $value))
                ->column();
        }
        else {
            $this->campaignTypeId = null;
        }

        return $this;
    }

    /**
     * Sets the [[campaignTypeId]] property.
     */
    public function campaignTypeId(array|int|null $value): static
    {
        $this->campaignTypeId = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function beforePrepare(): bool
    {
        $this->joinElementTable('campaign_campaigns');

        $this->query->select([
            'campaign_campaigns.campaignTypeId',
            'campaign_campaigns.recipients',
            'campaign_campaigns.opened',
            'campaign_campaigns.clicked',
            'campaign_campaigns.opens',
            'campaign_campaigns.clicks',
            'campaign_campaigns.unsubscribed',
            'campaign_campaigns.bounced',
            'campaign_campaigns.complained',
            'campaign_campaigns.dateClosed',
        ]);

        if ($this->campaignTypeId) {
            $this->subQuery->andWhere(Db::parseParam('campaign_campaigns.campaignTypeId', $this->campaignTypeId));
        }

        // Add the last sent date
        $sendoutQuery = SendoutRecord::find()
            ->select('campaignId, MAX([[lastSent]]) AS lastSent')
            ->groupBy('campaignId');

        $this->query->addSelect('lastSent');
        $this->subQuery->leftJoin(['campaign_sendouts' => $sendoutQuery], '[[campaign_sendouts.campaignId]] = [[campaign_campaigns.id]]');
        $this->subQuery->select('campaign_sendouts.lastSent AS lastSent');

        // Filter by campaign types in sites that have not been deleted
        $this->subQuery->innerJoin(CampaignTypeRecord::tableName().' campaign_campaigntypes', '[[campaign_campaigntypes.id]] = [[campaign_campaigns.campaignTypeId]]');
        $this->subQuery->innerJoin(Table::SITES.' sites', '[[sites.id]] = [[campaign_campaigntypes.siteId]]');
        $this->subQuery->andWhere(['[[sites.dateDeleted]]' => null]);

        return parent::beforePrepare();
    }

    /**
     * @inheritdoc
     */
    protected function statusCondition(string $status): mixed
    {
        return match ($status) {
            CampaignElement::STATUS_SENT => [
                'and',
                [
                    'elements.enabled' => 1,
                    'campaign_campaigns.dateClosed' => null,
                ],
                ['>', 'campaign_campaigns.recipients', 0]
            ],
            CampaignElement::STATUS_PENDING => [
                'and',
                [
                    'elements.enabled' => 1,
                    'campaign_campaigns.dateClosed' => null,
                    'campaign_campaigns.recipients' => 0,
                ]
            ],
            CampaignElement::STATUS_CLOSED => [
                'and',
                ['elements.enabled' => 1],
                ['not', ['campaign_campaigns.dateClosed' => null]],
            ],
            default => parent::statusCondition($status),
        };
    }
}
