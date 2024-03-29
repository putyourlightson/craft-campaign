<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\fieldlayoutelements\reports;

/**
 * @since 2.0.0
 */
class ContactReportFieldLayoutTab extends BaseReportFieldLayoutTab
{
    /**
     * @inheritdoc
     */
    public function getElements(): array
    {
        return [
            new ContactReportFieldLayoutElement(),
        ];
    }
}
