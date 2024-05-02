<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\fields;

use Craft;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\ElementCollection;
use craft\fields\BaseRelationField;
use putyourlightson\campaign\elements\CampaignElement;
use putyourlightson\campaign\elements\db\CampaignElementQuery;

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

    /**
     * @inheritdoc
     */
    public static function phpType(): string
    {
        return sprintf('\\%s|\\%s<\\%s>', CampaignElementQuery::class, ElementCollection::class, CampaignElement::class);
    }

    /**
     * @inheritdoc
     */
    protected function createSelectionCondition(): ?ElementConditionInterface
    {
        return CampaignElement::createCondition();
    }
}
