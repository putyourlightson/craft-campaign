<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\records;

use craft\db\ActiveRecord;
use craft\records\Site;

/**
 * MailingListTypeSiteRecord
 *
 * @property int $id ID
 * @property int $mailingListTypeId Mailing list type ID
 * @property int $siteId Site ID
 * @property string $verifyEmailTemplate
 * @property string $subscribeSuccessTemplate
 * @property string $unsubscribeSuccessTemplate
 * @property MailingListRecord $mailingListType Mailing list type
 * @property Site $site Site
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.4.0
 */
class MailingListTypeSiteRecord extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName(): string
    {
        return '{{%mailinglisttypes_sites}}';
    }

    /**
     * Returns the associated campaign type.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getMailingListType(): ActiveQueryInterface
    {
        return $this->hasOne(MailingListTypeRecord::class, ['id' => 'mailingListTypeId']);
    }

    /**
     * Returns the associated site.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getSite(): ActiveQueryInterface
    {
        return $this->hasOne(Site::class, ['id' => 'siteId']);
    }
}
