<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\records;

use craft\db\ActiveRecord;

/**
 * @property int $id ID
 * @property string $sid SID
 * @property string $fromName From name
 * @property string $fromEmail From email
 * @property string $replyToEmail Reply to email
 * @property string $to To
 * @property string $subject Subject
 * @property string $htmlBody HTML body
 * @property string $plaintextBody Plaintext body
 */
class BatchEmailRecord extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%campaign_batchemails}}';
    }
}
