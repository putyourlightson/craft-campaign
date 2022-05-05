<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\test\fixtures\elements;

use craft\base\ElementInterface;
use craft\test\fixtures\elements\BaseElementFixture;
use putyourlightson\campaign\elements\SegmentElement;

/**
 * @since 1.13.0
 */
abstract class SegmentElementFixture extends BaseElementFixture
{
    /**
     * @inheritdoc
     */
    protected function createElement(): ElementInterface
    {
        return new SegmentElement();
    }
}
