<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaigntests\fixtures;

use craft\test\ActiveFixture;
use putyourlightson\campaign\elements\CampaignElement;
use putyourlightson\campaign\records\LinkRecord;

/**
 * @since 1.12.2
 */
class LinksFixture extends ActiveFixture
{
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
    public ?int $campaignId = null;

    /**
     * @inheritdoc
     */
    public function load(): void
    {
        $campaign = CampaignElement::find()->one();
        $this->campaignId = $campaign ? $campaign->id : null;

        parent::load();
    }
}
