<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaigntests\fixtures;

use putyourlightson\campaign\test\fixtures\elements\CampaignElementFixture;

/**
 * @since 1.10.0
 */
class CampaignsFixture extends CampaignElementFixture
{
    /**
     * @inheritdoc
     */
    public $dataFile = __DIR__ . '/data/campaigns.php';

    /**
     * @inheritdoc
     */
    public $depends = [CampaignTypesFixture::class];
}
