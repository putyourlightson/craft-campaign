<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\fieldlayoutelements\reports;

/**
 * @since 2.0.0
 */
class MailingListReportFieldLayoutTab extends BaseReportFieldLayoutTab
{
    /**
     * @inheritdoc
     */
    public function getElements(): array
    {
        return [
            new MailingListReportFieldLayoutElement(),
        ];
    }
}
