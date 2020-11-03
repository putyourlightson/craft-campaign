<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\twigextensions;

use Html2Text\Html2Text;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * CampaignTwigExtension
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 */
class CampaignTwigExtension extends AbstractExtension
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getFilters()
    {
        return [
            new TwigFilter('html_to_plaintext', [$this, 'htmlToPlaintext']),
        ];
    }

    /**
     * Converts HTML to plaintext (with line breaks)
     *
     * @param string|null $html
     *
     * @return string
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
        $text = str_replace('[[br]]', "\r\n", $text);

        return $text;
    }
}
