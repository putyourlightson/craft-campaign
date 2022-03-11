<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\helpers;

class NumberHelper
{
    /**
     * Round the number down or return 1 if it is a fraction greater than zero.
     */
    public static function floorOrOne(float $value): int
    {
        if ($value > 0 && $value < 1) {
            return 1;
        }

        return (int)floor($value);
    }
}
