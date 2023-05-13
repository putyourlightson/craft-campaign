<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\records;

use craft\db\ActiveRecord;
use DateTime;

/**
 * @property int $id ID
 * @property int $assetId Asset ID
 * @property string $fileNameFile name
 * @property string $filePathFile path
 * @property int $userGroupId User group ID
 * @property int $userId User ID
 * @property int $mailingListId Mailing list ID
 * @property bool $unsubscribe
 * @property bool $forceSubscribe
 * @property string $emailFieldIndex Email field index
 * @property mixed $fieldIndexesField
 * @property int $added Added
 * @property int $updated Updated
 * @property int $failures Failures
 * @property DateTime $dateImportedDate imported
 */
class ImportRecord extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%campaign_imports}}';
    }
}
