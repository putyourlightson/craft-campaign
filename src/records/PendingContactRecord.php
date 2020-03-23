<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\records;

use putyourlightson\campaign\base\BaseActiveRecord;
use yii\db\ActiveQuery;

/**
 * PendingContactRecord
 *
 * @property int         $id                         ID
 * @property string      $pid                        Pending ID
 * @property string      $email                      Email
 * @property int         $mailingListId              Mailing List ID
 * @property string      $source                     Source
 * @property mixed       $fieldData                  Field data
 *
 * @method static ActiveQuery find()
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 */
class PendingContactRecord extends BaseActiveRecord
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
        return '{{%campaign_pendingcontacts}}';
    }
}
