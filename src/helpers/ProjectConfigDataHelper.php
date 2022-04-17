<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\helpers;

use craft\db\Table;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\models\CampaignTypeModel;
use putyourlightson\campaign\models\MailingListTypeModel;

/**
 * @since 1.12.0
 */
class ProjectConfigDataHelper
{
    /**
     * Rebuilds the project config.
     */
    public static function rebuildProjectConfig(): array
    {
        $configData = [
            'contactFieldLayout' => null,
            'campaignTypes' => [],
            'mailingListTypes' => [],
        ];

        $contactFieldLayout = Campaign::$plugin->settings->getContactFieldLayout();

        $configData['contactFieldLayout'][$contactFieldLayout->uid] = $contactFieldLayout->getConfig();

        $campaignTypes = Campaign::$plugin->campaignTypes->getAllCampaignTypes();

        foreach ($campaignTypes as $campaignType) {
            $configData['campaignTypes'][$campaignType->uid] = self::getCampaignTypeData($campaignType);
        }

        $mailingListTypes = Campaign::$plugin->mailingListTypes->getAllMailingListTypes();

        foreach ($mailingListTypes as $mailingListType) {
            $configData['mailingListTypes'][$mailingListType->uid] = self::getMailingListTypeData($mailingListType);
        }

        return $configData;
    }

    /**
     * Returns the campaign type data.
     */
    public static function getCampaignTypeData(CampaignTypeModel $campaignType): array
    {
        // Get config data from attributes
        $configData = $campaignType->getAttributes(null, ['id', 'siteId', 'fieldLayoutId', 'uid']);

        // Set the site UID
        $configData['siteUid'] = Db::uidById(Table::SITES, $campaignType->siteId);

        // Set the field layout
        $fieldLayout = $campaignType->getFieldLayout();
        $fieldLayoutConfig = $fieldLayout->getConfig();

        if ($fieldLayoutConfig) {
            if (empty($fieldLayout->id)) {
                $layoutUid = StringHelper::UUID();
                $fieldLayout->uid = $layoutUid;
            }
            else {
                $layoutUid = Db::uidById(Table::FIELDLAYOUTS, $fieldLayout->id);
            }

            $configData['fieldLayouts'] = [$layoutUid => $fieldLayoutConfig];
        }

        return $configData;
    }

    /**
     * Returns the mailing list type data.
     */
    public static function getMailingListTypeData(MailingListTypeModel $mailingListType): array
    {
        // Get config data from attributes
        $configData = $mailingListType->getAttributes(null, ['id', 'siteId', 'fieldLayoutId', 'uid']);

        // Set the site UID
        $configData['siteUid'] = Db::uidById(Table::SITES, $mailingListType->siteId);

        // Set the field layout
        $fieldLayout = $mailingListType->getFieldLayout();
        $fieldLayoutConfig = $fieldLayout->getConfig();

        if ($fieldLayoutConfig) {
            if (empty($fieldLayout->id)) {
                $layoutUid = StringHelper::UUID();
                $fieldLayout->uid = $layoutUid;
            }
            else {
                $layoutUid = Db::uidById(Table::FIELDLAYOUTS, $fieldLayout->id);
            }

            $configData['fieldLayouts'] = [$layoutUid => $fieldLayoutConfig];
        }

        return $configData;
    }
}
