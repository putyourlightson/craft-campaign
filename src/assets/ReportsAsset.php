<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\assets;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * ReportsAsset bundle
 */
class ReportsAsset extends AssetBundle
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
        $this->css = [
            'https://cdn.datatables.net/v/dt/dt-1.10.25/r-2.2.9/datatables.min.css',
            'css/chart.css',
            'css/datatables.css',
        ];
        $this->js = [
            'https://cdn.jsdelivr.net/npm/apexcharts@3',
            'https://cdn.datatables.net/v/dt/dt-1.10.25/r-2.2.9/datatables.min.js',
            'js/Chart.js',
            'js/DataTable.js',
        ];

        parent::init();
    }
}
