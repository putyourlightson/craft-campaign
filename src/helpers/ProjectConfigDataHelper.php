<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\helpers;

use Craft;
use putyourlightson\campaign\Campaign;

/**
 * ProjectConfigDataHelper
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.12.0
 */
class ProjectConfigDataHelper
{
    // Static Methods
    // =========================================================================

    /**
     * Rebuild project config
     *
     * @return array
     */
    public static function rebuildProjectConfig(): array
    {
        $data = [];

        // Campaign types
        $campaignTypes = Campaign::$plugin->campaignTypes->getAllCampaignTypes();

        foreach ($campaignTypes as $campaignType) {
            $campaignTypeData = $campaignType->getAttributes();

            if (!empty($campaignTypeData['fieldLayoutId'])) {
                $layout = Craft::$app->getFields()->getLayoutById($campaignTypeData['fieldLayoutId']);

                if ($layout) {
                    $campaignTypeData['fieldLayouts'] = [$layout->uid => $layout->getConfig()];
                }
            }

            unset($campaignTypeData['uid'], $campaignTypeData['fieldLayoutId']);

            $data['campaignTypes'][$campaignType->uid] = $campaignTypeData;
        }

        // Mailing list types
        $mailingListTypes = Campaign::$plugin->mailingListTypes->getAllMailingListTypes();

        foreach ($mailingListTypes as $mailingListType) {
            $mailingListTypeData = $mailingListType->getAttributes();

            if (!empty($mailingListTypeData['fieldLayoutId'])) {
                $layout = Craft::$app->getFields()->getLayoutById($mailingListTypeData['fieldLayoutId']);

                if ($layout) {
                    $mailingListTypeData['fieldLayouts'] = [$layout->uid => $layout->getConfig()];
                }
            }

            unset($mailingListTypeData['uid'], $mailingListTypeData['fieldLayoutId']);

            $data['mailingListTypes'][$mailingListType->uid] = $mailingListTypeData;
        }

        return $data;
    }
}
