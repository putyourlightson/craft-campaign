<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\fieldlayoutelements\reports;

use Craft;
use craft\base\ElementInterface;
use putyourlightson\campaign\elements\CampaignElement;

/**
 * @since 2.0.0
 */
class CampaignReportField extends BaseReportField
{
    /**
     * @inheritdoc
     * @param CampaignElement $element
     */
    public function formHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::$app->view->renderTemplate(
            'campaign/reports/campaigns/_includes/report',
            [
                'campaign' => $element->getCanonical(),
            ],
        );
    }
}
