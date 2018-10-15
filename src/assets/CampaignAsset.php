<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\assets;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * CampaignAsset bundle
 */
class CampaignAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = '@putyourlightson/campaign/resources';

        $this->depends = [
            CpAsset::class,
        ];

        // define the relative path to CSS/JS files that should be registered with the page when this asset bundle is registered
        $this->css = [
            'css/cp.css',
            'css/flag-icon.min.css',
        ];
        $this->js = [
            'js/Campaign.js',
            'js/Cp.js',
            'js/CampaignIndex.js',
            'js/MailingListIndex.js',
            'js/SegmentIndex.js',
            'js/SendoutIndex.js',
        ];

        parent::init();
    }
}
