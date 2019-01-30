<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\records;

use craft\db\ActiveRecord;
use putyourlightson\campaign\helpers\StringHelper;

/**
 * LinkRecord
 *
 * @property int         $id                         ID
 * @property string      $lid                        Link ID
 * @property int         $campaignId                 Campaign ID
 * @property string      $url                        URL
 * @property string      $title                      Title
 * @property int         $clicked                    Clicked
 * @property int         $clicks                     Clicks
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0   
 */
class LinkRecord extends ActiveRecord
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
        return '{{%campaign_links}}';
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function beforeSave($insert): bool
    {
        if ($insert) {
            // Create unique ID
            $this->lid = StringHelper::uniqueId('l');
        }

        return parent::beforeSave($insert);
    }
}
