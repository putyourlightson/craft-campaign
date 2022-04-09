<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\records;

use craft\db\ActiveRecord;
use craft\db\SoftDeleteTrait;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * @property int $id ID
 * @property string $pid Pending ID
 * @property string $email Email
 * @property int $mailingListId Mailing List ID
 * @property string $source Source
 * @property mixed $fieldData Field data
 *
 * @mixin SoftDeleteBehavior
 */
class PendingContactRecord extends ActiveRecord
{
    use SoftDeleteTrait;

    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%campaign_pendingcontacts}}';
    }
}
