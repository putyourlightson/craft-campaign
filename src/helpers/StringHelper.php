<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\helpers;

use Html2Text\Html2Text;

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

    /**
     * Converts HTML to plaintext (with line breaks).
     */
    public static function htmlToPlaintext(string $html = null): string
    {
        if ($html === null) {
            return '';
        }

        // Convert <br> tags to avoid losing them
        $html = preg_replace('/<br\s?\/?>/i', '[[br]]', $html);

        // Convert to plaintext
        $html2Text =new Html2Text($html);
        $plaintext = $html2Text->getText();

        // Convert [[br]] tags to new lines
        return str_replace('[[br]]', PHP_EOL, $plaintext);
    }
}
