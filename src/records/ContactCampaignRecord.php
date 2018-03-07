<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\records;

use craft\db\ActiveRecord;
use putyourlightson\campaign\elements\CampaignElement;

/**
 * ContactCampaignRecord
 *
 * @property int         $id                         ID
 * @property int         $contactId                  Contact ID
 * @property int         $campaignId                 Campaign ID
 * @property int         $sendoutId                  SID
 * @property \DateTime   $opened                     Opened
 * @property \DateTime   $clicked                    Clicked
 * @property \DateTime   $unsubscribed               Unsubscribed
 * @property \DateTime   $complained                 Complained
 * @property \DateTime   $bounced                    Bounced
 * @property int         $opens                      Opens
 * @property int         $clicks                     Clicks
 * @property string      $links                      Links
 * @property string      $country                    Country
 * @property string      $geoIp                      GeoIP
 * @property string      $device                     Device
 * @property string      $os                         OS
 * @property string      $client                     Client
 *
 * @property CampaignElement $campaign               Campaign
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0   
 */
class ContactCampaignRecord extends ActiveRecord
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
        return '{{%campaign_contacts_campaigns}}';
    }
}
