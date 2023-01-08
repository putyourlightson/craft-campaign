<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\assets;

use craft\web\AssetBundle;

class WidgetAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = '@putyourlightson/campaign/resources';

    /**
     * @inheritdoc
     */
    public $css = [
        'css/widget.css',
    ];
}
