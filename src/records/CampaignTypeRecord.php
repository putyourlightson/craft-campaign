<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\records;

use craft\db\ActiveRecord;
use craft\records\Site;
use yii\db\ActiveQuery;

/**
 * @property int $id
 * @property int $siteId
 * @property int $fieldLayoutId
 * @property string $name
 * @property string $handle
 * @property string $uriFormat
 * @property string $htmlTemplate
 * @property string $plaintextTemplate
 * @property string $queryStringParameters
 * @property string|null $testContactIds
 * @property string $uid
 *
 * @property-read Site|null $site
 */
class CampaignTypeRecord extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%campaign_campaigntypes}}';
    }

    /**
     * Returns the associated site.
     */
    public function getSite(): ActiveQuery
    {
        return $this->hasOne(Site::class, ['id' => 'siteId']);
    }
}
