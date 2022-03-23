<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\fieldlayoutelements\reports;

use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\BaseNativeField;
use putyourlightson\campaign\assets\ReportsAsset;

/**
 * @since 2.0.0
 */
abstract class BaseReportField extends BaseNativeField
{
    /**
     * @inheritdoc
     */
    public string $attribute = 'report';

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        Craft::$app->getView()->registerAssetBundle(ReportsAsset::class);
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        return null;
    }
}
