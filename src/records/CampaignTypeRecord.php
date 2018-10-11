<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\records;

use craft\db\ActiveRecord;
use craft\records\Site;
use yii\db\ActiveQueryInterface;


/**
 * CampaignTypeRecord
 *
 * @property int         $id                    ID
 * @property int         $siteId                Site ID
 * @property int         $fieldLayoutId         Field layout ID
 * @property string      $name                  Name
 * @property string      $handle                Handle
 * @property string      $uriFormat             URI format
 * @property string      $htmlTemplate          HTML template
 * @property string      $plaintextTemplate     Plaintext template
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
     * Returns the associated site.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getSite(): ActiveQueryInterface
    {
        return $this->hasOne(Site::class, ['id' => 'siteId']);
    }
}
