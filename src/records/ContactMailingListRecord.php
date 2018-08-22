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
 * @property int              $id                         ID
 * @property int              $contactId                  Contact ID
 * @property int              $mailingListId              Mailing list ID
 * @property string           $subscriptionStatus         Subscription status
 * @property \DateTime|null   $subscribed                 Subscribed
 * @property \DateTime|null   $unsubscribed               Unsubscribed
 * @property \DateTime|null   $complained                 Complained
 * @property \DateTime|null   $bounced                    Bounced
 * @property \DateTime|null   $verified                   Verified
 * @property string           $sourceType                 Source type
 * @property string           $source                     Source
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
