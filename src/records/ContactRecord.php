<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\records;

use craft\db\ActiveRecord;

/**
 * ContactRecord
 *
 * @property int         $id                         ID
 * @property string      $cid                        Contact ID
 * @property string      $email                      Email
 * @property bool        $pending                    Pending
 * @property string      $country                    Country
 * @property string      $geoIp                      GeoIP
 * @property string      $device                     Device
 * @property string      $os                         OS
 * @property string      $client                     Client
 * @property \DateTime   $lastActivity               Last activity
 * @property \DateTime   $complained                 Complained
 * @property \DateTime   $bounced                    Bounced
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0   
 */
class ContactRecord extends ActiveRecord
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
        return '{{%campaign_contacts}}';
    }
}
