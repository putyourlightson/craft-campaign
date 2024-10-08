<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\records;

use craft\db\ActiveQuery;
use craft\db\ActiveRecord;
use craft\db\Table;
use DateTime;

/**
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
 *
 * @property-read ContactRecord|null $contact
 * @property-read MailingListRecord|null $mailingList
 */
class ContactMailingListRecord extends ActiveRecord
{
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
        // Create a subquery to ensure only contacts and mailing lists that are not drafts not deleted are returned
        $subquery = parent::find()
            ->innerJoin(Table::ELEMENTS . ' contactElement', '[[contactElement.id]] = [[contactId]]')
            ->innerJoin(Table::ELEMENTS . ' mailingListElement', '[[mailingListElement.id]] = [[mailingListId]]')
            ->where([
                'contactElement.draftId' => null,
                'mailingListElement.draftId' => null,
                'contactElement.dateDeleted' => null,
                'mailingListElement.dateDeleted' => null,
            ]);

        return parent::find()->from(['subquery' => $subquery]);
    }

    /**
     * Returns the related contact record.
     */
    public function getContact(): ActiveQuery
    {
        return $this->hasOne(ContactRecord::class, ['id' => 'contactId']);
    }

    /**
     * Returns the related mailing list record.
     */
    public function getMailingList(): ActiveQuery
    {
        return $this->hasOne(MailingListRecord::class, ['id' => 'mailingListId']);
    }
}
