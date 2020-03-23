<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\records;

use craft\db\Table;
use DateTime;
use putyourlightson\campaign\base\BaseActiveRecord;
use yii\db\ActiveQuery;

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
        /** @var ActiveQuery $query */
        $query = parent::find();

        // Ensure contact is not deleted
        $query->innerJoin(Table::ELEMENTS.' contactElement', '[[contactElement.id]] = [[contactId]]')
            ->where(['contactElement.dateDeleted' => null]);

        // Ensure campaign is not deleted
        $query->innerJoin(Table::ELEMENTS.' campaignElement', '[[campaignElement.id]] = [[campaignId]]')
            ->where(['campaignElement.dateDeleted' => null]);

        return $query;
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
