<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\records;

use craft\records\Site;
use putyourlightson\campaign\base\BaseActiveRecord;
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
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 */
class CampaignTypeRecord extends BaseActiveRecord
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

    /**
     * @return ActiveQuery
     */
    public static function find()
    {
        return parent::find()
            ->innerJoinWith(['site site'])
            ->where(['site.dateDeleted' => null]);
    }

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
