<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\assets;

use craft\web\AssetBundle;

class ContactEditAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = '@putyourlightson/campaign/resources';

    /**
     * @inheritdoc
     */
    public $depends = [
        CampaignAsset::class,
    ];

    /**
     * @inheritdoc
     */
    public $js = [
        'js/ContactEdit.js',
    ];
}
