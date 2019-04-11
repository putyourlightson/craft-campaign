<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\records;

use craft\db\ActiveRecord;
use DateTime;
use yii\db\ActiveQuery;
use yii\db\ActiveQueryInterface;

/**
 * ContactMailingListRecord
 *
 * @property int $id
 * @property int $contactId
 * @property int $mailingListId
 * @property string $subscriptionStatus
 * @property DateTime|null $subscribed
 * @property DateTime|null $unsubscribed
 * @property DateTime|null $complained
 * @property DateTime|null $bounced
 * @property DateTime|null $verified
 * @property string $sourceType
 * @property string $source
 * @property ActiveQueryInterface $contact
 * @property ActiveQueryInterface $mailingList
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
            ->innerJoinWith(['mailingList' => function(ActiveQuery $query) {
                $query->innerJoinWith('element mailingList_element')
                    ->where(['mailingList_element.dateDeleted' => null]);
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
     * Returns the related mailing list record.
     *
     * @return ActiveQueryInterface
     */
    public function getMailingList(): ActiveQueryInterface
    {
        return $this->hasOne(MailingListRecord::class, ['id' => 'mailingListId']);
    }
}
