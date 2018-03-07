<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\helpers;

/**
 * StringHelper
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 */
class StringHelper extends \craft\helpers\StringHelper
{
    // Public Methods
    // =========================================================================

    /**
     * Generates a unique ID with an optional prefix
     *
     * @param string|null $prefix
     *
     * @return string
     */
    public static function uniqueId(string $prefix = ''): string
    {
        return uniqid($prefix, false).self::randomString(3);
    }

    /**
     * Returns a class name without the namespace
     *
     * @param string $class
     *
     * @return string
     */
    public static function getClassName(string $class): string
    {
        return basename(str_replace('\\', '/', $class));
    }
}