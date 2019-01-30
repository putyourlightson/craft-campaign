<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\records;

use craft\db\ActiveRecord;
use craft\records\Element;
use yii\db\ActiveQueryInterface;

/**
 * MailingListRecord
 *
 * @property int $id
 * @property int|null $mailingListTypeId
 * @property int|null $syncedUserGroupId
 * @property ActiveQueryInterface $mailingListType
 * @property ActiveQueryInterface $element
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
     * @return ActiveQueryInterface The relational query object.
     */
    public function getMailingListType(): ActiveQueryInterface
    {
        return $this->hasOne(MailingListTypeRecord::class, ['id' => 'mailingListTypeId']);
    }

    /**
     * Returns the related element.
     *
     * @return ActiveQueryInterface
     */
    public function getElement(): ActiveQueryInterface
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }
}
