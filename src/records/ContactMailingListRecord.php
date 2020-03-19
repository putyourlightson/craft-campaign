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
class ContactMailingListRecord extends BaseActiveRecord
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
        // Create subquery to ensure only contacts and mailing lists that are not deleted are returned
        $subquery = parent::find()
            ->innerJoin(Table::ELEMENTS.' contactElement', '[[contactElement.id]] = [[contactId]]')
            ->innerJoin(Table::ELEMENTS.' mailingListElement', '[[mailingListElement.id]] = [[mailingListId]]')
            ->where([
                'contactElement.dateDeleted' => null,
                'mailingListElement.dateDeleted' => null,
            ]);

        $query = parent::find()->from($subquery);

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
