<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\records;

use craft\db\ActiveRecord;
use craft\records\Element;
use yii\db\ActiveQuery;

/**
 * MailingListRecord
 *
 * @property int $id
 * @property int|null $mailingListTypeId
 * @property int|null $syncedUserGroupId
 * @property ActiveQuery $mailingListType
 * @property ActiveQuery $element
 *
 * @method static ActiveQuery find()
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 */
class MailingListRecord extends ActiveRecord
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
        return '{{%campaign_mailinglists}}';
    }

    // Public Methods
    // =========================================================================

    /**
     * Returns the campaign type.
     *
     * @return ActiveQuery
     */
    public function getMailingListType(): ActiveQuery
    {
        return $this->hasOne(MailingListTypeRecord::class, ['id' => 'mailingListTypeId']);
    }

    /**
     * Returns the related element.
     *
     * @return ActiveQuery
     */
    public function getElement(): ActiveQuery
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }
}
