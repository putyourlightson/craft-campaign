<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\services;

use craft\records\Element_SiteSettings;
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
        // Get site ID from element site settings
        $siteId = Element_SiteSettings::find()
            ->select('siteId')
            ->where(['elementId' => $segmentId])
            ->scalar();

        if ($siteId === null) {
            return null;
        }

        $segment = SegmentElement::find()
            ->id($segmentId)
            ->siteId($siteId)
            ->status(null)
            ->one();

        return $segment;
    }

    /**
     * Returns segments by IDs
     *
     * @param int $siteId
     * @param int[] $segmentIds
     *
     * @return SegmentElement[]
     */
    public function getSegmentsByIds(int $siteId, array $segmentIds): array
    {
        if (empty($segmentIds)) {
            return [];
        }

        return SegmentElement::find()
            ->id($segmentIds)
            ->siteId($siteId)
            ->status(null)
            ->all();
    }
}