<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\elements\db;

use putyourlightson\campaign\elements\CampaignElement;
use putyourlightson\campaign\models\CampaignTypeModel;
use putyourlightson\campaign\records\CampaignTypeRecord;

use craft\elements\db\ElementQuery;
use craft\helpers\Db;
use yii\db\Connection;

/**
 * CampaignElementQuery
 *
 * @method CampaignElement[]|array all($db = null)
 * @method CampaignElement|array|null one($db = null)
 * @method CampaignElement|array|null nth(int $n, Connection $db = null)
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0   
 */
class CampaignElementQuery extends ElementQuery
{
    // Properties
    // =========================================================================

    /**
     * @var int|int[]|null The campaign type ID(s) that the resulting campaigns must have.
     */
    public $campaignTypeId;

    // Public Methods
    // =========================================================================

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
     *
     * @param string|string[]|CampaignTypeModel|null $value The property value
     *
     * @return static self reference
     */
    public function campaignType($value)
    {
        if ($value instanceof CampaignTypeModel) {
            $this->campaignTypeId = $value->id;
        }
        else if ($value !== null) {
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
     *
     * @param int|int[]|null $value The property value
     *
     * @return static self reference
     */
    public function campaignTypeId($value)
    {
        $this->campaignTypeId = $value;

        return $this;
    }

    // Protected Methods
    // =========================================================================

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

        $this->subQuery->innerJoin(CampaignTypeRecord::tableName().' campaign_campaigntypes', '[[campaign_campaigntypes.id]] = [[campaign_campaigns.campaignTypeId]]');
        $this->subQuery->select('campaign_campaigntypes.name AS campaignType');

        return parent::beforePrepare();
    }

    /**
     * @inheritdoc
     */
    protected function statusCondition(string $status)
    {
        switch ($status) {
            case CampaignElement::STATUS_SENT:
                return [
                    'and',
                    [
                        'elements.enabled' => 1,
                        'campaign_campaigns.dateClosed' => null,
                    ],
                    ['>', 'campaign_campaigns.recipients', 0]
                ];
            case CampaignElement::STATUS_PENDING:
                return [
                    'and',
                    [
                        'elements.enabled' => 1,
                        'campaign_campaigns.dateClosed' => null,
                        'campaign_campaigns.recipients' => 0,
                    ]
                ];
            case CampaignElement::STATUS_CLOSED:
                return [
                    'and',
                    ['elements.enabled' => 1],
                    ['not', ['campaign_campaigns.dateClosed' => null]],
                ];
            default:
                return parent::statusCondition($status);
        }
    }
}
