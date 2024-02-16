<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\helpers;

/**
 * @since 1.8.2
 */
class SendoutHelper
{
    /**
     * Returns the provided memory converted to bytes.
     */
    public static function memoryInBytes(string $value): int
    {
        $unit = strtolower(substr($value, -1, 1));
        $unitValue = match ($unit) {
            'g' => pow(1024, 3),
            'm' => pow(1024, 2),
            'k' => 1024,
            default => 1,
        };

        return (int)$value * $unitValue;
    }
}
