<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\assets;

use craft\web\AssetBundle;

class ReportsAsset extends AssetBundle
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
    public $css = [
        'https://cdn.datatables.net/v/dt/dt-1.10.25/r-2.2.9/datatables.min.css',
        'css/chart.css',
        'css/datatables.css',
    ];

    /**
     * @inheritdoc
     */
    public $js = [
        'https://cdn.jsdelivr.net/npm/apexcharts@3',
        'https://cdn.datatables.net/v/dt/dt-1.10.25/r-2.2.9/datatables.min.js',
        'js/Chart.js',
        'js/DataTable.js',
    ];
}
