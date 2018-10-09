<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\records;

use craft\db\ActiveRecord;
use craft\records\Site;

/**
 * CampaignTypeSiteRecord
 *
 * @property int $id ID
 * @property int $campaignTypeId Campaign type ID
 * @property int $siteId Site ID
 * @property string $uriFormat URI format
 * @property string $htmlTemplate HTML template
 * @property string $plaintextTemplate Plaintext template
 * @property CampaignTypeRecord $campaignType Campaign type
 * @property Site $site Site
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.4.0
 */
class CampaignTypeSiteRecord extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName(): string
    {
        return '{{%campaigntypes_sites}}';
    }

    /**
     * Returns the associated campaign type.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getCampaignType(): ActiveQueryInterface
    {
        return $this->hasOne(CampaignTypeRecord::class, ['id' => 'campaignTypeId']);
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
