<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\records;

use craft\db\ActiveRecord;

/**
 * ContactMailingListRecord
 *
 * @property int         $id                         ID
 * @property int         $contactId                  Contact ID
 * @property int         $mailingListId              Mailing list ID
 * @property string      $subscriptionStatus         Subscription status
 * @property \DateTime   $subscribed                 Subscribed
 * @property \DateTime   $unsubscribed               Unsubscribed
 * @property \DateTime   $complained                 Complained
 * @property \DateTime   $bounced                    Bounced
 * @property \DateTime   $confirmed                  Confirmed
 * @property string      $source                     Source
 * @property string      $sourceUrl                  Source URL
 * @property string      $country                    Country
 * @property string      $geoIp                      GeoIP
 * @property string      $device                     Device
 * @property string      $os                         OS
 * @property string      $client                     Client
 * @property string      $pendingContactData         Pending contact data
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 */
class ContactMailingListRecord extends ActiveRecord
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
        return '{{%campaign_contacts_mailinglists}}';
    }
}
