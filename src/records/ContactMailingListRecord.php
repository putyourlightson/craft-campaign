<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\records;

use craft\db\ActiveRecord;
use craft\db\Table;
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
 * @property string|int|null $source
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

        // Ensure contact is not deleted
        $query->innerJoin(Table::ELEMENTS.' contactElement', '[[contactElement.id]] = [[contactId]]')
            ->where(['contactElement.dateDeleted' => null]);

        // Ensure mailing list is not deleted
        $query->innerJoin(Table::ELEMENTS.' mailingListElement', '[[mailingListElement.id]] = [[mailingListId]]')
            ->where(['mailingListElement.dateDeleted' => null]);

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
     * Returns the related mailing list record.
     *
     * @return ActiveQuery
     */
    public function getMailingList(): ActiveQuery
    {
        return $this->hasOne(MailingListRecord::class, ['id' => 'mailingListId']);
    }
}
