<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\records;

use craft\records\Element;
use DateTime;
use putyourlightson\campaign\base\BaseActiveRecord;
use yii\db\ActiveQuery;

/**
 * CampaignRecord
 *
 * @property int $id
 * @property int $campaignTypeId
 * @property int $recipients
 * @property int $opened
 * @property int $clicked
 * @property int $opens
 * @property int $clicks
 * @property int $unsubscribed
 * @property int $complained
 * @property int $bounced
 * @property DateTime|null $dateClosed
 * @property ActiveQuery $campaignType
 * @property ActiveQuery $element
 *
 * @method static ActiveQuery find()
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 */
class CampaignRecord extends BaseActiveRecord
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%campaign_campaigns}}';
    }

    /**
     * Returns the campaign type.
     *
     * @return ActiveQuery
     */
    public function getCampaignType(): ActiveQuery
    {
        return $this->hasOne(CampaignTypeRecord::class, ['id' => 'campaignTypeId']);
    }

    /**
     * Returns the related element.
     *
     * @return ActiveQuery
     */
    public function getElement(): ActiveQuery
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }
}
