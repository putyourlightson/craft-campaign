<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\helpers;

/**
 * SendoutHelper
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.8.2
 */
class SendoutHelper
{
    // Static Methods
    // =========================================================================

    /**
     * Returns the provided memory converted to bytes
     *
     * @param string $value
     *
     * @return int
     */
    public static function memoryInBytes(string $value): int
    {
        $unit = strtolower(substr($value, -1, 1));
        $value = (int)$value;
        switch($unit) {
            case 'g':
                $value *= pow(1024, 3);
                break;
            case 'm':
                $value *= pow(1024, 2);
                break;
            case 'k':
                $value *= 1024;
                break;
        }

        return $value;
    }
}
