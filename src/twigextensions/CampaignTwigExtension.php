<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\twigextensions;

use PurpleBooth\HtmlStripperImplementation;

/**
 * CampaignTwigExtension
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0   
 */
class CampaignTwigExtension extends \Twig_Extension implements \Twig_Extension_GlobalsInterface
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getFilters()
    {
        return [
            new \Twig_Filter('html_to_plaintext', [$this, 'htmlToPlaintext']),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getGlobals()
    {
        return [];
    }

    /**
     * Converts HTML to plaintext (with line breaks)
     *
     * @param string $html
     *
     * @return string
     */
    public function htmlToPlaintext(string $html): string
    {
        // Convert <br> tags to avoid losing them
        $html = preg_replace('/<br\s?\/?>/i', '[[br]]', $html);

        // Convert to text
        $htmlStripper = new HtmlStripperImplementation();
        $text = $htmlStripper->toText($html);

        // Convert [[br]] tags to new lines
        $text = str_replace('[[br]]', "\r\n", $text);

        return $text;
    }
}
