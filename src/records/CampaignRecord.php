<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\records;

use craft\db\ActiveRecord;
use craft\records\Element;
use DateTime;
use yii\db\ActiveQueryInterface;

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
 * @property ActiveQueryInterface $campaignType
 * @property ActiveQueryInterface $element
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 */
class CampaignRecord extends ActiveRecord
{
    // Public Static Methods
    // =========================================================================

     /**
     * @inheritdoc
     *
     * @return string the table name
     */
    public static function tableName(): string
    {
        return '{{%campaign_campaigns}}';
    }

    // Public Methods
    // =========================================================================

    /**
     * Returns the campaign type.
     *
     * @return ActiveQueryInterface
     */
    public function getCampaignType(): ActiveQueryInterface
    {
        return $this->hasOne(CampaignTypeRecord::class, ['id' => 'campaignTypeId']);
    }

    /**
     * Returns the related element.
     *
     * @return ActiveQueryInterface
     */
    public function getElement(): ActiveQueryInterface
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }
}
