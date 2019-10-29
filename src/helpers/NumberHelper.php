<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\helpers;

/**
 * NumberHelper
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 */
class NumberHelper
{
    // Static Methods
    // =========================================================================

    /**
     * Round the number down or return 1 if it is a fraction greater than zero
     *
     * @param float $value
     *
     * @return int
     */
    public static function floorOrOne(float $value): int
    {
        if ($value > 0 && $value < 1) {
            return 1;
        }

        return floor($value);
    }
}
