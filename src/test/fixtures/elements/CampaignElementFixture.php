<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\test\fixtures\elements;

use craft\test\fixtures\elements\ElementFixture;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\CampaignElement;
use putyourlightson\campaigntests\fixtures\CampaignTypesFixture;

/**
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.10.0
 */

abstract class CampaignElementFixture extends ElementFixture
{
    // Public Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    public $modelClass = CampaignElement::class;

    /**
     * @var array
     */
    public $campaignTypeIds = [];

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function load()
    {
        foreach (Campaign::$plugin->campaignTypes->getAllCampaignTypes() as $campaignType) {
            $this->campaignTypeIds[$campaignType->handle] = $campaignType->id;
        }

        parent::load();
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function isPrimaryKey(string $key): bool
    {
        return parent::isPrimaryKey($key) || in_array($key, ['campaignTypeId', 'title']);
    }
}
