<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\helpers;

use Craft;

class CsvHelper
{
    /**
     * @since 3.9.0
     */
    public const DELIMITERS = ['comma', 'semicolon', 'tab'];

    /**
     * Returns the available CSV delimiters as options.
     *
     * @since 3.9.0
     */
    public static function getDelimiterOptions(): array
    {
        return [
            ['label' => Craft::t('campaign', 'Comma'), 'value' => 'comma'],
            ['label' => Craft::t('campaign', 'Semicolon'), 'value' => 'semicolon'],
            ['label' => Craft::t('campaign', 'Tab'), 'value' => 'tab'],
        ];
    }

    /**
     * Returns the character represented by a CSV delimiter.
     *
     * @since 3.9.0
     */
    public static function getDelimiterCharacter(string $delimiter): string
    {
        return match ($delimiter) {
            'semicolon' => ';',
            'tab' => "\t",
            default => ',',
        };
    }
}
