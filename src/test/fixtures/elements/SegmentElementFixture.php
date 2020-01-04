<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\test\fixtures\elements;

use craft\test\fixtures\elements\ElementFixture;
use putyourlightson\campaign\elements\SegmentElement;

/**
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.13.0
 */

abstract class SegmentElementFixture extends ElementFixture
{
    // Public Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    public $modelClass = SegmentElement::class;

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function isPrimaryKey(string $key): bool
    {
        return parent::isPrimaryKey($key) || in_array($key, ['title']);
    }
}
