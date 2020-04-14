<?php
/**
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
    // Static Methods
    // =========================================================================

    /**
     * Generates a 17 character unique ID with an optional prefix
     *
     * @param string $prefix
     *
     * @return string
     */
    public static function uniqueId(string $prefix = ''): string
    {
        $uniqueId = uniqid($prefix, false).self::randomString(4);

        return substr($uniqueId, 0, 17);
    }
}
