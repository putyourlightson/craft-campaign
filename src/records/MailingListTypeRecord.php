<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\records;

use craft\db\ActiveRecord;


/**
 * MailingListTypeRecord
 *
 * @property int         $id                            ID
 * @property int         $fieldLayoutId Field layout    ID
 * @property string      $name                          Name
 * @property string      $handle                        Handle
 * @property bool        $doubleOptIn                   Double opt-in
 * @property string      $subscribeSuccessTemplate      Subscribe success template
 * @property string      $unsubscribeSuccessTemplate    Unsubscribe success template
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0   
 */
class MailingListTypeRecord extends ActiveRecord
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
        return '{{%campaign_mailinglisttypes}}';
    }
}
