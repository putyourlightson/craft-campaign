<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaigntests\fixtures;

use craft\test\ActiveFixture;
use putyourlightson\campaign\records\CampaignTypeRecord;

/**
 * @since 1.10.0
 */
class CampaignTypesFixture extends ActiveFixture
{
    /**
     * @inheritdoc
     */
    public $dataFile = __DIR__ . '/data/campaign-types.php';

    /**
     * @inheritdoc
     */
    public $modelClass = CampaignTypeRecord::class;
}
