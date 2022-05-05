<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\twigextensions;

use putyourlightson\campaign\helpers\StringHelper;
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
        return StringHelper::htmlToPlaintext($html);
    }
}
