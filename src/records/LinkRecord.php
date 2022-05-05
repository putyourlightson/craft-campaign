<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\records;

use craft\db\ActiveRecord;
use putyourlightson\campaign\helpers\StringHelper;

/**
 * @property int $id ID
 * @property string $lid Link ID
 * @property int $campaignId Campaign ID
 * @property string $url URL
 * @property string $title Title
 * @property int $clicked Clicked
 * @property int $clicks Clicks
 */
class LinkRecord extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%campaign_links}}';
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert): bool
    {
        if ($insert) {
            // Create unique ID
            $this->lid = StringHelper::uniqueId('l');
        }

        return parent::beforeSave($insert);
    }
}
