<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\records;

use craft\db\ActiveRecord;

/**
 * MailingListRecord
 *
 * @property int         $id                         ID
 * @property int|null    $mailingListTypeId          Mailing list type ID
 * @property int|null    $syncedUserGroupId          Synced user group ID
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

    /**
     * Returns the associated sites.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getSites(): ActiveQueryInterface
    {
        return $this->hasMany(MailingListTypeSiteRecord::class, ['mailingListTypeId' => 'id']);
    }
}
