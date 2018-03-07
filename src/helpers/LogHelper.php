<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\helpers;

use Craft;

/**
 * LogHelper
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 */
class LogHelper
{
    // Public Methods
    // =========================================================================

    /**
     * Logs a user action
     *
     * @param string $message
     * @param array $params
     * @param string|null $category
     */
    public static function logUserAction(string $message, array $params, string $category = 'Campaign')
    {
        $params['username'] = Craft::$app->getUser()->getIdentity()->username;

        Craft::info(Craft::t('campaign', $message, $params), $category);
    }
}