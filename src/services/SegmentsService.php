<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\services;

use putyourlightson\campaign\elements\SegmentElement;

use craft\base\Component;

/**
 * SegmentsService
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 *
 * @property array $segmentAvailableFields
 * @property array $segmentFieldOperators
 */
class SegmentsService extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * Returns segment by ID
     *
     * @param int $segmentId
     *
     * @return SegmentElement|null
     */
    public function getSegmentById(int $segmentId)
    {
        if (!$segmentId) {
            return null;
        }

        $segment = SegmentElement::find()
            ->id($segmentId)
            ->status(null)
            ->one();

        return $segment;
    }
}