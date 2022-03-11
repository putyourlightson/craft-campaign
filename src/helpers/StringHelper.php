<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\helpers;

class StringHelper extends \craft\helpers\StringHelper
{
    /**
     * Generates a 17 character unique ID with an optional prefix.
     */
    public static function uniqueId(string $prefix = ''): string
    {
        $uniqueId = uniqid($prefix, false) . self::randomString(4);

        return substr($uniqueId, 0, 17);
    }
}
