<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\assets;

use craft\web\AssetBundle;

class ReportsAsset extends AssetBundle
{
    /**
     * Download builder: https://datatables.net/download/
     * - Extensions
     *   - Buttons
     *     - HTML5 export
     *   - Responsive
     */
    public const DATATABLES_BASE_URL = 'https://cdn.datatables.net/v/dt/dt-1.11.5/b-2.2.2/b-html5-2.2.2/r-2.2.9/';

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
        self::DATATABLES_BASE_URL . 'datatables.min.css',
        'css/chart.css',
        'css/datatables.css',
    ];

    /**
     * @inheritdoc
     */
    public $js = [
        'https://cdn.jsdelivr.net/npm/apexcharts@3',
        self::DATATABLES_BASE_URL . 'datatables.min.js',
        'js/Chart.js',
        'js/DataTable.js',
        'js/Report.js',
    ];
}
