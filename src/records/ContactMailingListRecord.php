<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\records;

use craft\db\ActiveRecord;
use yii\db\ActiveQueryInterface;

/**
 * ContactMailingListRecord
 *
 * @property int $id
 * @property int $contactId
 * @property int $mailingListId
 * @property string $subscriptionStatus
 * @property \DateTime|null $subscribed
 * @property \DateTime|null $unsubscribed
 * @property \DateTime|null $complained
 * @property \DateTime|null $bounced
 * @property \DateTime|null $verified
 * @property string $sourceType
 * @property string $source
 * @property ActiveQueryInterface $contact
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

    /**
     * Returns the related contact record
     *
     * @return ActiveQueryInterface
     */
    public function getContact(): ActiveQueryInterface
    {
        return $this->hasOne(ContactRecord::class, ['id' => 'contactId']);
    }
}
