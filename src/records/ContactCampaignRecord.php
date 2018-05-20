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
 * @property int         $sendoutId                  Sendout ID
 * @property int         $mailingListId              Mailing List ID
 * @property \DateTime   $sent                       Sent
 * @property \DateTime   $failed                     Failed
 * @property \DateTime   $opened                     Opened
 * @property \DateTime   $clicked                    Clicked
 * @property \DateTime   $unsubscribed               Unsubscribed
 * @property \DateTime   $complained                 Complained
 * @property \DateTime   $bounced                    Bounced
 * @property int         $opens                      Opens
 * @property int         $clicks                     Clicks
 * @property string      $links                      Links
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
