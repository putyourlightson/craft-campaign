<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\fieldlayoutelements\segments;

use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\BaseNativeField;
use putyourlightson\campaign\elements\SegmentElement;

/**
 * @since 2.0.0
 */
class SegmentFieldLayoutElement extends BaseNativeField
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
        return Craft::$app->getView()->renderTemplate('campaign/segments/_includes/fields', [
            'segment' => $element,
        ]);
    }
}
