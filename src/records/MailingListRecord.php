<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\records;

use craft\db\ActiveRecord;
use craft\records\Element;
use yii\db\ActiveQuery;

/**
 * @property int $id
 * @property int|null $mailingListTypeId
 * @property int|null $syncedUserGroupId
 *
 * @property-read MailingListTypeRecord|null $mailingListType
 * @property-read Element|null $element
 */
class MailingListRecord extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%campaign_mailinglists}}';
    }

    /**
     * Returns the campaign type.
     */
    public function getMailingListType(): ActiveQuery
    {
        return $this->hasOne(MailingListTypeRecord::class, ['id' => 'mailingListTypeId']);
    }

    /**
     * Returns the related element.
     */
    public function getElement(): ActiveQuery
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }
}
