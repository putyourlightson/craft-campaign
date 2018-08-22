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
 * @property int              $id                         ID
 * @property int              $contactId                  Contact ID
 * @property int              $campaignId                 Campaign ID
 * @property int              $sendoutId                  Sendout ID
 * @property int              $mailingListId              Mailing List ID
 * @property \DateTime|null   $sent                       Sent
 * @property \DateTime|null   $failed                     Failed
 * @property \DateTime|null   $opened                     Opened
 * @property \DateTime|null   $clicked                    Clicked
 * @property \DateTime|null   $unsubscribed               Unsubscribed
 * @property \DateTime|null   $complained                 Complained
 * @property \DateTime|null   $bounced                    Bounced
 * @property int              $opens                      Opens
 * @property int              $clicks                     Clicks
 * @property string|null      $links                      Links
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
