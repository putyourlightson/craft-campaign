<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\fieldlayoutelements\reports;

use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\BaseNativeField;

/**
 * @since 2.0.0
 */
class ReportField extends BaseNativeField
{
    /**
     * @inheritdoc
     */
    public string $attribute = 'report';

    /**
     * @inheritdoc
     */
    public function formHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::$app->view->renderTemplate(
            'campaign/reports/campaigns/_includes/report',
            ['campaign' => $element],
        );
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        return null;
    }
}
