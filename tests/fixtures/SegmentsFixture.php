<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaigntests\fixtures;

use putyourlightson\campaign\test\fixtures\elements\SegmentElementFixture;

/**
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.13.0
 */

class SegmentsFixture extends SegmentElementFixture
{
    // Public Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    public $dataFile = __DIR__ . '/data/segments.php';
}
