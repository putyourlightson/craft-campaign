<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\records;

use craft\db\ActiveRecord;


/**
 * CampaignTypeRecord
 *
 * @property int         $id                    ID
 * @property int         $fieldLayoutId         Field layout ID
 * @property string      $name                  Name
 * @property string      $handle                Handle
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 */
class CampaignTypeRecord extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     *
     * @return string
     */
    public static function tableName(): string
    {
        return '{{%campaign_campaigntypes}}';
    }

    /**
     * Returns the associated sites.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getSites(): ActiveQueryInterface
    {
        return $this->hasMany(CampaignTypeSiteRecord::class, ['campaignTypeId' => 'id']);
    }
}
