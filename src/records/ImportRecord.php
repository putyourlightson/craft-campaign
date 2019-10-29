<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\records;

use craft\db\ActiveRecord;
use DateTime;


/**
 * ImportRecord
 *
 * @property int         $id                    ID
 * @property string      $fileName              File name
 * @property string      $filePath              File path
 * @property int         $userGroupId           User group ID
 * @property int         $userId                User ID
 * @property int         $mailingListId         Mailing list ID
 * @property string      $emailFieldIndex       Email field index
 * @property mixed       $fieldIndexes          Field indexes
 * @property int         $added                 Added
 * @property int         $updated               Updated
 * @property int         $fails                 Fails
 * @property mixed       $failures              Failures
 * @property DateTime    $dateImported          Date imported
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 */
class ImportRecord extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     *
     * @return string
     */
    public static function tableName(): string
    {
        return '{{%campaign_imports}}';
    }
}
