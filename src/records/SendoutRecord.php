<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\records;

use craft\db\ActiveRecord;
use DateTime;
use yii\db\ActiveQuery;

/**
 * SendoutRecord
 *
 * @property int         $id                         ID
 * @property string      $sid                        SID
 * @property int         $campaignId                 Campaign ID
 * @property int         $senderId                   Sender ID
 * @property string      $sendoutType                Sendout type
 * @property string      $sendStatus                 Send status
 * @property string      $fromName                   From name
 * @property string      $fromEmail                  From email
 * @property string      $replyToEmail               Reply to email
 * @property string      $subject                    Subject
 * @property string      $notificationEmailAddress   Notification email address
 * @property string      $mailingListIds             Mailing list IDs
 * @property string      $excludedMailingListIds     Excluded mailing list IDs
 * @property int         $recipients                 Recipients
 * @property int         $fails                      Fails
 * @property mixed       $schedule                   Schedule
 * @property string      $htmlBody                   HTML body
 * @property string      $plaintextBody              Plaintext body
 * @property DateTime    $sendDate                   Send date
 * @property DateTime    $lastSent                   Last sent
 *
 * @method static ActiveQuery find()
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 */
class SendoutRecord extends ActiveRecord
{
    // Public Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%campaign_sendouts}}';
    }
}
