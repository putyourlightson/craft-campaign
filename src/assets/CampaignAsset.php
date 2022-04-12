<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\assets;

use craft\web\AssetBundle;

class CampaignAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = '@putyourlightson/campaign/resources';

    /**
     * @inheritdoc
     */
    public $depends = [
        CpAsset::class,
    ];

    /**
     * @inheritdoc
     */
    public $css = [
        'css/campaign.css',
        'css/flag-icon.min.css',
    ];

    /**
     * @inheritdoc
     */
    public $js = [
        'js/Campaign.js',
        'js/Cp.js',
        'js/CampaignIndex.js',
        'js/ContactIndex.js',
        'js/MailingListIndex.js',
        'js/SegmentIndex.js',
        'js/SendoutIndex.js',
    ];
}
