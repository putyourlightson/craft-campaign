<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\records;

use craft\db\ActiveRecord;
use craft\records\Site;
use yii\db\ActiveQuery;


/**
 * CampaignTypeRecord
 *
 * @property int $id
 * @property int $siteId
 * @property int $fieldLayoutId
 * @property string $name
 * @property string $handle
 * @property string $uriFormat
 * @property string $htmlTemplate
 * @property string $plaintextTemplate
 * @property string $queryStringParameters
 * @property Site|null $site
 *
 * @method static ActiveQuery find()
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 */
class CampaignTypeRecord extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%campaign_campaigntypes}}';
    }

    // Public Methods
    // =========================================================================

    /**
     * Returns the associated site.
     *
     * @return ActiveQuery
     */
    public function getSite(): ActiveQuery
    {
        return $this->hasOne(Site::class, ['id' => 'siteId']);
    }
}
