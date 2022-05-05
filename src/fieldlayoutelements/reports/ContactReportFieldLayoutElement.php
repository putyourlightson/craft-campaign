<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\fieldlayoutelements\reports;

use Craft;
use craft\base\ElementInterface;
use putyourlightson\campaign\elements\ContactElement;

/**
 * @since 2.0.0
 */
class ContactReportFieldLayoutElement extends BaseReportFieldLayoutElement
{
    /**
     * @inheritdoc
     * @param ContactElement $element
     */
    public function formHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::$app->getView()->renderTemplate(
            'campaign/reports/contacts/_includes/report',
            [
                'contact' => $element->getCanonical(),
            ],
        );
    }
}
