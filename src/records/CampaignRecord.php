<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\records;

use craft\base\Element;
use craft\db\ActiveRecord;
use yii\db\ActiveQueryInterface;

/**
 * CampaignRecord
 *
 * @property int                          $id                         ID
 * @property int                          $campaignTypeId             Campaign Type ID
 * @property int                          $recipients                 Recipients
 * @property int                          $opened                     Opened
 * @property int                          $clicked                    Clicked
 * @property int                          $opens                      Opens
 * @property int                          $clicks                     Clicks
 * @property int                          $unsubscribed               Unsubscribed
 * @property int                          $complained                 Complained
 * @property int                          $bounced                    Bounced
 * @property ActiveQueryInterface         $element
 * @property \DateTime|null               $dateClosed                 Date closed
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

    /**
     * Returns the element.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getElement(): ActiveQueryInterface
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }
}
