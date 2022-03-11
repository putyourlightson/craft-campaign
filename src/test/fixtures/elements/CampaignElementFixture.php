<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\test\fixtures\elements;

use craft\base\ElementInterface;
use craft\test\fixtures\elements\BaseElementFixture;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\CampaignElement;

/**
 * @since 1.10.0
 */
abstract class CampaignElementFixture extends BaseElementFixture
{
    /**
     * @var array
     */
    public array $campaignTypeIds = [];

    /**
     * @inheritdoc
     */
    public function load(): void
    {
        foreach (Campaign::$plugin->campaignTypes->getAllCampaignTypes() as $campaignType) {
            $this->campaignTypeIds[$campaignType->handle] = $campaignType->id;
        }

        parent::load();
    }

    /**
     * @inheritdoc
     */
    protected function createElement(): ElementInterface
    {
        return new CampaignElement();
    }
}
