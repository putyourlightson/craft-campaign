<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\assets;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * CampaignEditAsset bundle
 */
class CampaignEditAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = '@putyourlightson/campaign/resources';

        $this->depends = [
            CpAsset::class,
            CampaignAsset::class,
        ];

        // define the relative path to CSS/JS files that should be registered with the page when this asset bundle is registered
        $this->js = [
            'js/CampaignEdit.js',
        ];

        parent::init();
    }
}
