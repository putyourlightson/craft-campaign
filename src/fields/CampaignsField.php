<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\fields;

use Craft;
use craft\fields\BaseRelationField;
use putyourlightson\campaign\elements\CampaignElement;

class CampaignsField extends BaseRelationField
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('campaign', 'Campaign Campaigns');
    }

    /**
     * @inheritdoc
     */
    public static function elementType(): string
    {
        return CampaignElement::class;
    }

    /**
     * @inheritdoc
     */
    public static function defaultSelectionLabel(): string
    {
        return Craft::t('campaign', 'Add a campaign');
    }
}
