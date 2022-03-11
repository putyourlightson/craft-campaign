<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\records;

use craft\db\ActiveQuery;
use craft\db\Table;
use DateTime;
use putyourlightson\campaign\base\BaseActiveRecord;

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
 * @property ActiveQuery $contact
 * @property ActiveQuery $campaign
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 */
class ContactCampaignRecord extends BaseActiveRecord
{
    // Public Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%campaign_contacts_campaigns}}';
    }

    /**
     * @inheritdoc
     */
    public static function find(): ActiveQuery
    {
        // Create a subquery to ensure only contacts and campaigns that are not deleted are returned
        $subquery = parent::find()
            ->innerJoin(Table::ELEMENTS . ' contactElement', '[[contactElement.id]] = [[contactId]]')
            ->innerJoin(Table::ELEMENTS . ' campaignElement', '[[campaignElement.id]] = [[campaignId]]')
            ->where([
                'contactElement.dateDeleted' => null,
                'campaignElement.dateDeleted' => null,
            ]);

        return parent::find()->from(['subquery' => $subquery]);
    }

    // Public Methods
    // =========================================================================

    /**
     * Returns the related contact record.
     *
     * @return ActiveQuery
     */
    public function getContact(): ActiveQuery
    {
        return $this->hasOne(ContactRecord::class, ['id' => 'contactId']);
    }

    /**
     * Returns the related campaign record.
     *
     * @return ActiveQuery
     */
    public function getCampaign(): ActiveQuery
    {
        return $this->hasOne(CampaignRecord::class, ['id' => 'campaignId']);
    }
}
