<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\fieldlayoutelements\reports;

use Craft;
use craft\base\ElementInterface;
use putyourlightson\campaign\elements\MailingListElement;

/**
 * @since 2.0.0
 */
class MailingListReportField extends BaseReportField
{
    /**
     * @inheritdoc
     * @param MailingListElement $element
     */
    public function formHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::$app->view->renderTemplate(
            'campaign/reports/mailinglists/_includes/report',
            [
                'mailingList' => $element->getCanonical(),
            ],
        );
    }
}
