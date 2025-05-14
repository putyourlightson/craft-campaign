<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\integrations\sidekick;

use Craft;
use craft\helpers\Json;
use doublesecretagency\sidekick\models\SkillResponse;
use doublesecretagency\sidekick\skills\BaseSkillSet;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\models\CampaignTypeModel;
use Throwable;

/**
 * @category Campaign types
 */
class CampaignTypesSkills extends BaseSkillSet
{
    /**
     * @inheritdoc
     */
    protected function restrictedMethods(): array
    {
        $restrictedMethods = [];
        $config = Craft::$app->getConfig()->getGeneral();
        if (!$config->allowAdminChanges) {
            $restrictedMethods[] = 'createCampaignType';
            $restrictedMethods[] = 'updateCampaignType';
            $restrictedMethods[] = 'deleteCampaignType';
        }

        return $restrictedMethods;
    }

    /**
     * Get a complete list of existing campaign types.
     *
     * If you are unfamiliar with the existing campaign types, you MUST call this tool before creating, reading, updating, or deleting campaign types.
     * Eagerly call this if an understanding of the current campaign types is required.
     *
     * You may also find it helpful to call this tool before updating a Campaign.
     *
     * @return SkillResponse
     */
    public static function getCampaignTypes(): SkillResponse
    {
        $campaignTypes = [];
        $allCampaignTypes = Campaign::$plugin->campaignTypes->getAllCampaignTypes();

        foreach ($allCampaignTypes as $campaignType) {
            $campaignTypes = [];
            $campaignTypes[] = [
                'ID' => $campaignType->id,
                'Name' => $campaignType->name,
                'Handle' => $campaignType->handle,
            ];
        }

        return new SkillResponse([
            'success' => true,
            'message' => 'Reviewed the existing campaign types.',
            'response' => Json::encode($campaignTypes),
        ]);
    }

    /**
     * Create a new campaign type.
     *
     * @param string $campaignTypeConfig JSON-stringified configuration for the `CampaignType` model.
     * @return SkillResponse
     */
    public static function createCampaignType(string $campaignTypeConfig): SkillResponse
    {
        try {
            $campaignType = Json::decode($campaignTypeConfig);
            $campaignType = new CampaignTypeModel($campaignType);

            if (!$campaignType->validate()) {
                $errors = implode(', ', $campaignType->getErrorSummary(true));
                return new SkillResponse([
                    'success' => false,
                    'message' => "Invalid site configuration: $errors",
                ]);
            }

            if (!Campaign::$plugin->campaignTypes->saveCampaignType($campaignType)) {
                $errors = implode(', ', $campaignType->getErrorSummary(true));
                return new SkillResponse([
                    'success' => false,
                    'message' => "Failed to create campaign type: $errors",
                ]);
            }
        } catch (Throwable $e) {
            return new SkillResponse([
                'success' => false,
                'message' => "Unable to create the campaign type. {$e->getMessage()}",
            ]);
        }

        return new SkillResponse([
            'success' => true,
            'message' => "Campaign type \"{$campaignType['name']}\" with handle \"{$campaignType['handle']}\" has been created.",
        ]);
    }

    /**
     * Update an existing campaign type with a new configuration.
     *
     * Make sure you understand the EXISTING campaign type configuration before updating.
     * If needed, you MUST call `getCampaignTypes` to get the current configuration.
     *
     * For large updates, ask for confirmation before proceeding.
     *
     * @param string $campaignTypeHandle Handle of the campaign type to update.
     * @param string $newConfig JSON-stringified configuration for the campaign type.
     * @return SkillResponse
     */
    public static function updateCampaignType(string $campaignTypeHandle, string $newConfig): SkillResponse
    {
        try {
            $campaignType = Campaign::$plugin->campaignTypes->getCampaignTypeByHandle($campaignTypeHandle);

            if (!$campaignType) {
                return new SkillResponse([
                    'success' => false,
                    'message' => "Unable to update, campaign type `$campaignTypeHandle` does not exist.",
                ]);
            }

            $config = Json::decode($newConfig);
            if (!is_array($config)) {
                return new SkillResponse([
                    'success' => false,
                    'message' => 'Invalid JSON provided for campaign type configuration.',
                ]);
            }

            $campaignType->name = ($config['name'] ?? $campaignType->name);
            $campaignType->handle = ($config['handle'] ?? $campaignType->handle);

            if (!Campaign::$plugin->campaignTypes->saveCampaignType($campaignType)) {
                $errors = implode(', ', $campaignType->getErrorSummary(true));
                return new SkillResponse([
                    'success' => false,
                    'message' => "Failed to update campaign type: $errors",
                ]);
            }
        } catch (Throwable $e) {
            return new SkillResponse([
                'success' => false,
                'message' => "Unable to update the campaign type. {$e->getMessage()}",
            ]);
        }

        return new SkillResponse([
            'success' => true,
            'message' => "Campaign type \"$campaignType->name\" has been updated.",
        ]);
    }

    /**
     * Delete a campaign type by its handle.
     *
     * ALWAYS ASK FOR CONFIRMATION!! This is a very destructive action.
     *
     * Force the user to re-enter the campaign type handle they are deleting.
     *
     * @param string $handle Campaign type to delete.
     * @return SkillResponse
     */
    public static function deleteCampaignType(string $handle): SkillResponse
    {
        $campaignType = Campaign::$plugin->campaignTypes->getCampaignTypeByHandle($handle);

        if (!$campaignType) {
            return new SkillResponse([
                'success' => false,
                'message' => "Campaign type \"$handle\" not found.",
            ]);
        }

        try {
            if (!Campaign::$plugin->campaignTypes->deleteCampaignType($campaignType)) {
                $errors = implode(', ', $campaignType->getErrorSummary(true));
                return new SkillResponse([
                    'success' => false,
                    'message' => "Failed to delete campaign type: $errors",
                ]);
            }
        } catch (Throwable $e) {
            return new SkillResponse([
                'success' => false,
                'message' => "Unable to delete the campaign type. {$e->getMessage()}",
            ]);
        }

        return new SkillResponse([
            'success' => true,
            'message' => "Campaign type \"$campaignType->name\" has been deleted.",
        ]);
    }
}
