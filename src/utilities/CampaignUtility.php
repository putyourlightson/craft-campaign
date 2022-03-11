<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\utilities;

use Craft;
use craft\base\Utility;
use putyourlightson\campaign\Campaign;

class CampaignUtility extends Utility
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('campaign', 'Campaign');
    }

    /**
     * @inheritdoc
     */
    public static function id(): string
    {
        return 'campaign';
    }

    /**
     * @inheritdoc
     */
    public static function iconPath(): ?string
    {
        return Craft::getAlias('@vendor/putyourlightson/craft-campaign/src/icon-mask.svg');
    }

    /**
     * @inheritdoc
     */
    public static function contentHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('campaign/_utility', [
            'settings' => Campaign::$plugin->getSettings(),
        ]);
    }
}
