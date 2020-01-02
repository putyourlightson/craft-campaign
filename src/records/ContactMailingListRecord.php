<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\records;

use craft\db\ActiveRecord;
use DateTime;
use yii\db\ActiveQuery;

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
 * @property ActiveQuery $contact
 * @property ActiveQuery $mailingList
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
     */
    public static function tableName(): string
    {
        return '{{%campaign_contacts_mailinglists}}';
    }

    /**
     * @inheritdoc
     */
    public static function find(): ActiveQuery
    {
        /** @var ActiveQuery $query */
        $query = parent::find();

        return $query
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
     * @return ActiveQuery
     */
    public function getContact(): ActiveQuery
    {
        return $this->hasOne(ContactRecord::class, ['id' => 'contactId']);
    }

    /**
     * Returns the related mailing list record.
     *
     * @return ActiveQuery
     */
    public function getMailingList(): ActiveQuery
    {
        return $this->hasOne(MailingListRecord::class, ['id' => 'mailingListId']);
    }
}
