<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\helpers;

use Craft;
use LitEmoji\LitEmoji;

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

    /**
     * Encodes emojis if necessary.
     * TODO: remove in Campaign 3 when Craft 5 supports utf8mb4 encoding.
     *
     * @since 2.9.2
     */
    public static function encodeEmojis(?string $value): string
    {
        if (empty($value)) {
            return '';
        }

        if (!Craft::$app->getDb()->getIsMysql()) {
            return $value;
        }

        return LitEmoji::unicodeToShortcode($value);
    }

    /**
     * Decodes emojis if necessary.
     * TODO: remove in Campaign 3 when Craft 5 supports utf8mb4 encoding.
     *
     * @since 2.9.2
     */
    public static function decodeEmojis(?string $value): ?string
    {
        if (!Craft::$app->getDb()->getIsMysql()) {
            return $value;
        }

        if (empty($value)) {
            return '';
        }

        return LitEmoji::shortcodeToUnicode($value);
    }
}
