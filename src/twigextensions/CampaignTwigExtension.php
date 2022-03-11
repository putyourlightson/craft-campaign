<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\twigextensions;

use Html2Text\Html2Text;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class CampaignTwigExtension extends AbstractExtension
{
    /**
     * @inheritdoc
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('html_to_plaintext', [$this, 'htmlToPlaintext']),
        ];
    }

    /**
     * Converts HTML to plaintext (with line breaks).
     */
    public function htmlToPlaintext(string $html = null): string
    {
        if ($html === null) {
            return '';
        }

        // Convert <br> tags to avoid losing them
        $html = preg_replace('/<br\s?\/?>/i', '[[br]]', $html);

        // Convert to text
        $html2Text = new Html2Text($html);
        $text = $html2Text->getText();

        // Convert [[br]] tags to new lines
        return str_replace('[[br]]', "\r\n", $text);
    }
}
