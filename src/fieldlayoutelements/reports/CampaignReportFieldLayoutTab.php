<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\fieldlayoutelements\reports;

use craft\base\ElementInterface;
use putyourlightson\campaign\elements\CampaignElement;

/**
 * @since 2.0.0
 */
class CampaignReportFieldLayoutTab extends BaseReportFieldLayoutTab
{
    /**
     * @inheritdoc
     */
    public function getElements(): array
    {
        return [
            new CampaignReportField(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function showInForm(?ElementInterface $element = null): bool
    {
        return $element->getStatus() == CampaignElement::STATUS_SENT;
    }
}
