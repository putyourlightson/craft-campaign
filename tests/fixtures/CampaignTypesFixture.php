<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaigntests\fixtures;

use craft\test\ActiveFixture;
use putyourlightson\campaign\records\CampaignTypeRecord;

/**
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.10.0
 */

class CampaignTypesFixture extends ActiveFixture
{
    // Public Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    public $dataFile = __DIR__ . '/data/campaign-types.php';

    /**
     * @inheritdoc
     */
    public $modelClass = CampaignTypeRecord::class;
}
