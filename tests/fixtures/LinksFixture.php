<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaigntests\fixtures;

use craft\test\Fixture;
use putyourlightson\campaign\elements\CampaignElement;
use putyourlightson\campaign\records\LinkRecord;

/**
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.12.2
 */

class LinksFixture extends Fixture
{
    // Public Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    public $dataFile = __DIR__ . '/data/links.php';

    /**
     * @inheritdoc
     */
    public $modelClass = LinkRecord::class;

    /**
     * @inheritdoc
     */
    public $depends = [CampaignsFixture::class];

    /**
     * @var int|null
     */
    public $campaignId;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function load()
    {
        $campaign = CampaignElement::find()->one();
        $this->campaignId = $campaign ? $campaign->id : null;

        parent::load();
    }
}
