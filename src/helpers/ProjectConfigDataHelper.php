<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\helpers;

use Craft;
use craft\db\Table;
use craft\helpers\Db;
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
        $data['campaignTypes'] = self::_getCampaignTypeData();
        $data['mailingListTypes'] = self::_getMailingListTypeData();

        return $data;
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns campaign type data
     *
     * @return array
     */
    private static function _getCampaignTypeData(): array
    {
        $data = [];
        $campaignTypes = Campaign::$plugin->campaignTypes->getAllCampaignTypes();

        foreach ($campaignTypes as $campaignType) {
            // Get config data from attributes
            $configData = $campaignType->getAttributes(null, ['id', 'siteId', 'fieldLayoutId', 'uid']);

            // Set the site UID
            $configData['siteUid'] = Db::uidById(Table::SITES, $campaignType->siteId);

            if (!empty($campaignType['fieldLayoutId'])) {
                $layout = Craft::$app->getFields()->getLayoutById($campaignType['fieldLayoutId']);

                if ($layout) {
                    $configData['fieldLayouts'] = [$layout->uid => $layout->getConfig()];
                }
            }

            $data[$campaignType->uid] = $configData;
        }

        return $data;
    }

    /**
     * Returns mailing list type data
     *
     * @return array
     */
    private static function _getMailingListTypeData(): array
    {
        $data = [];
        $mailingListTypes = Campaign::$plugin->mailingListTypes->getAllMailingListTypes();

        foreach ($mailingListTypes as $mailingListType) {
            // Get config data from attributes
            $configData = $mailingListType->getAttributes(null, ['id', 'siteId', 'fieldLayoutId', 'uid']);

            // Set the site UID
            $configData['siteUid'] = Db::uidById(Table::SITES, $mailingListType->siteId);

            if (!empty($mailingListType['fieldLayoutId'])) {
                $layout = Craft::$app->getFields()->getLayoutById($mailingListType['fieldLayoutId']);

                if ($layout) {
                    $configData['fieldLayouts'] = [$layout->uid => $layout->getConfig()];
                }
            }

            unset($configData['uid'], $configData['fieldLayoutId']);

            $data[$mailingListType->uid] = $configData;
        }

        return $data;
    }
}
