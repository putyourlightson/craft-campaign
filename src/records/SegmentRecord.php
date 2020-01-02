<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\records;

use craft\db\ActiveRecord;
use yii\db\ActiveQuery;

/**
 * SegmentRecord
 *
 * @property int $id
 * @property string $segmentType
 * @property mixed $conditions
 *
 * @method static ActiveQuery find()
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 */
class SegmentRecord extends ActiveRecord
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
        return '{{%campaign_segments}}';
    }
}
