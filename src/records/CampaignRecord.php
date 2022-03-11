<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\records;

use craft\db\ActiveRecord;
use craft\records\Element;
use DateTime;
use yii\db\ActiveQuery;

/**
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
 *
 * @property-read CampaignTypeRecord $campaignType
 * @property-read Element $element
 */
class CampaignRecord extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%campaign_campaigns}}';
    }

    /**
     * Returns the campaign type.
     */
    public function getCampaignType(): ActiveQuery
    {
        return $this->hasOne(CampaignTypeRecord::class, ['id' => 'campaignTypeId']);
    }

    /**
     * Returns the related element.
     */
    public function getElement(): ActiveQuery
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }
}
