<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\integrations\sidekick;

use Craft;
use craft\helpers\Json;
use doublesecretagency\sidekick\helpers\ElementsHelper;
use doublesecretagency\sidekick\models\SkillResponse;
use doublesecretagency\sidekick\skills\BaseSkillSet;
use putyourlightson\campaign\elements\CampaignElement;
use putyourlightson\campaign\elements\db\CampaignElementQuery;
use Throwable;

/**
 * @category Campaigns
 */
class CampaignsSkills extends BaseSkillSet
{
    /**
     * Get basic information (id, title, slug) about all campaigns.
     *
     * Optionally specify a campaign type handle to filter the results.
     *
     * @param string $campaignType Optional handle of the campaign type to filter by. Set to empty string to get all campaigns.
     * @return SkillResponse
     */
    public static function getCampaignsInfo(string $campaignType): SkillResponse
    {
        /** @var CampaignElementQuery $query */
        $query = CampaignElement::find()->select(['id', 'title', 'slug']);

        if ($campaignType) {
            $query->campaignType($campaignType);
        }

        $campaigns = $query->all();

        $results = [];
        foreach ($campaigns as $campaign) {
            $results[] = [
                'id' => $campaign->id,
                'title' => $campaign->title,
                'slug' => $campaign->slug,
            ];
        }

        $inCampaignType = ($campaignType ? " in campaign type \"$campaignType\"" : '');

        if (!$results) {
            return new SkillResponse([
                'success' => false,
                'message' => "No campaigns found$inCampaignType.",
            ]);
        }

        return new SkillResponse([
            'success' => true,
            'message' => "Reviewed basic info for all campaigns$inCampaignType.",
            'response' => Json::encode($results),
        ]);
    }

    /**
     * Get a campaign.
     *
     * @param string $campaignId ID of the campaign to retrieve.
     * @return SkillResponse
     */
    public static function getCampaign(string $campaignId): SkillResponse
    {
        $campaign = Craft::$app->getElements()->getElementById($campaignId);

        if (!$campaign) {
            return new SkillResponse([
                'success' => false,
                'message' => "Can't find campaign with the ID $campaignId.",
            ]);
        }

        return new SkillResponse([
            'success' => true,
            'message' => "Retrieved campaign $campaignId.",
            'response' => Json::encode($campaign),
        ]);
    }

    /**
     * Create a new campaign.
     *
     * If you do not have a clear understanding of which campaign types exist, call the `getCampaignTypes` skill first.
     *
     * @param string $jsonConfig JSON-stringified configuration for the element. See the "Element Configs" instructions.
     * @return SkillResponse
     */
    public static function createCampaign(string $jsonConfig): SkillResponse
    {
        $campaign = new CampaignElement();

        ElementsHelper::populateElement($campaign, $jsonConfig);

        try {
            if (!Craft::$app->elements->saveElement($campaign)) {
                return new SkillResponse([
                    'success' => false,
                    'message' => 'Failed to create campaign: ' . implode(', ', $campaign->getErrorSummary(true)),
                ]);
            }
        } catch (Throwable $e) {
            return new SkillResponse([
                'success' => false,
                'message' => "Unable to create the campaign. $e>getMessage()}",
            ]);
        }

        return new SkillResponse([
            'success' => true,
            'message' => "Campaign \"$campaign>title}\" has been created.",
        ]);
    }

    /**
     * Update an existing campaign.
     *
     * @param string $campaignId ID of the campaign to update.
     * @param string $jsonConfig JSON-stringified configuration for the element. See the "Element Configs" instructions.
     * @return SkillResponse
     */
    public static function updateCampaign(string $campaignId, string $jsonConfig): SkillResponse
    {
        $campaign = Craft::$app->getElements()->getElementById($campaignId);

        if (!$campaign) {
            return new SkillResponse([
                'success' => false,
                'message' => "Can't find campaign with the ID $campaignId.",
            ]);
        }

        ElementsHelper::populateElement($campaign, $jsonConfig);

        try {
            if (!Craft::$app->elements->saveElement($campaign)) {
                return new SkillResponse([
                    'success' => false,
                    'message' => 'Failed to update campaign: ' . implode(', ', $campaign->getErrorSummary(true)),
                ]);
            }
        } catch (Throwable $e) {
            return new SkillResponse([
                'success' => false,
                'message' => "Unable to update the campaign. $e>getMessage()}",
            ]);
        }

        return new SkillResponse([
            'success' => true,
            'message' => "Campaign \"$campaign>title}\" has been updated.",
        ]);
    }

    /**
     * Delete a campaign.
     *
     * ALWAYS ASK FOR CONFIRMATION!! This is a very destructive action.
     *
     * Force the user to re-enter the slug of the campaign they are deleting.
     *
     * @param string $campaignId ID of the campaign to delete.
     * @return SkillResponse
     */
    public static function deleteCampaign(string $campaignId): SkillResponse
    {
        try {
            Craft::$app->getElements()->deleteElementById($campaignId);
        } catch (Throwable $e) {
            return new SkillResponse([
                'success' => false,
                'message' => "Unable to delete campaign $campaignId. {$e->getMessage()}",
            ]);
        }

        return new SkillResponse([
            'success' => true,
            'message' => "Successfully deleted campaign $campaignId.",
        ]);
    }
}
