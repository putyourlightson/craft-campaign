<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\fieldlayoutelements\reports;

use craft\base\ElementInterface;
use craft\fieldlayoutelements\BaseNativeField;

/**
 * @since 2.0.0
 */
abstract class BaseReportField extends BaseNativeField
{
    /**
     * @inheritdoc
     */
    public string $attribute = 'reports';

    /**
     * @inheritdoc
     */
    protected function inputHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        return null;
    }
}
