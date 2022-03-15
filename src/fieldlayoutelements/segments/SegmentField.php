<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\fieldlayoutelements\segments;

use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\BaseNativeField;
use putyourlightson\campaign\assets\SegmentEditAsset;
use putyourlightson\campaign\elements\SegmentElement;
use putyourlightson\campaign\helpers\SegmentHelper;

/**
 * @since 2.0.0
 */
class SegmentField extends BaseNativeField
{
    /**
     * @inheritdoc
     */
    public string $attribute = 'segment';

    /**
     * @inheritdoc
     */
    protected function inputHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        return null;
    }

    /**
     * @inheritdoc
     * @param SegmentElement $element
     */
    public function formHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        Craft::$app->view->registerAssetBundle(SegmentEditAsset::class);

        return Craft::$app->view->renderTemplate(
            'campaign/segments/_includes/segmentTypes/' . $element->segmentType,
            [
                'segment' => $element,
                'availableFields' => SegmentHelper::getAvailableFields(),
                'fieldOperators' => SegmentHelper::getFieldOperators(),
            ],
        );
    }
}
