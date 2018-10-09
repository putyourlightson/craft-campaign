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
 * @property int         $fieldLayoutId                 Field layout ID
 * @property string      $name                          Name
 * @property string      $handle                        Handle
 * @property bool        $doubleOptIn                   Double opt-in
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

    /**
     * Returns the associated site settings.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getSiteSettings(): ActiveQueryInterface
    {
        return $this->hasMany(MailingListTypeSiteModel::class, ['mailingListTypeId' => 'id']);
    }
}
