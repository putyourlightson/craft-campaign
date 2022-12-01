<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\helpers;

use Craft;

/**
 * @since 2.3.0
 */
class UrlHelper extends \craft\helpers\UrlHelper
{
    /**
     * Returns a (front-end) site action URL.
     */
    public static function siteActionUrl(string $path = '', array|string|null $params = null, ?string $scheme = null, ?bool $showScriptName = null): string
    {
        $request = Craft::$app->getRequest();
        $isCpRequest = $request->getIsCpRequest();
        Craft::$app->getRequest()->setIsCpRequest(false);
        $actionUrl = parent::actionUrl($path, $params, $scheme, $showScriptName);
        Craft::$app->getRequest()->setIsCpRequest($isCpRequest);

        return $actionUrl;
    }
}
