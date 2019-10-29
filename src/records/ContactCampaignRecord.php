<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\records;

use craft\db\ActiveRecord;
use DateTime;
use yii\db\ActiveQuery;
use yii\db\ActiveQueryInterface;

/**
 * ContactCampaignRecord
 *
 * @property int $id ID
 * @property int $contactId Contact ID
 * @property int $campaignId Campaign ID
 * @property int $sendoutId Sendout ID
 * @property int $mailingListId Mailing List ID
 * @property DateTime|null $sent Sent
 * @property DateTime|null $opened Opened
 * @property DateTime|null $clicked Clicked
 * @property DateTime|null $unsubscribed Unsubscribed
 * @property DateTime|null $complained Complained
 * @property DateTime|null $bounced Bounced
 * @property int $opens Opens
 * @property int $clicks Clicks
 * @property string|null $links Links
 * @property ActiveQueryInterface $contact
 * @property ActiveQueryInterface $campaign
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

    /**
     * @inheritdoc
     *
     * @return ActiveQuery
     */
    public static function find()
    {
        return parent::find()
            ->innerJoinWith(['contact' => function(ActiveQuery $query) {
                $query->innerJoinWith('element contact_element')
                    ->where(['contact_element.dateDeleted' => null]);
            }])
            ->innerJoinWith(['campaign' => function(ActiveQuery $query) {
                $query->innerJoinWith('element campaign_element')
                    ->where(['campaign_element.dateDeleted' => null]);
            }]);
    }

    // Public Methods
    // =========================================================================

    /**
     * Returns the related contact record.
     *
     * @return ActiveQueryInterface
     */
    public function getContact(): ActiveQueryInterface
    {
        return $this->hasOne(ContactRecord::class, ['id' => 'contactId']);
    }

    /**
     * Returns the related campaign record.
     *
     * @return ActiveQueryInterface
     */
    public function getCampaign(): ActiveQueryInterface
    {
        return $this->hasOne(CampaignRecord::class, ['id' => 'campaignId']);
    }
}
