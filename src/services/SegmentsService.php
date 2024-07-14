<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\services;

use craft\base\Component;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\db\ContactElementQuery;
use putyourlightson\campaign\elements\SegmentElement;

/**
 * @since 1.9.0
 *
 * @property-read SegmentElement[] $allSegments
 */
class SegmentsService extends Component
{
    /**
     * Returns a segment by ID.
     */
    public function getSegmentById(int $segmentId): ?SegmentElement
    {
        /** @var SegmentElement|null */
        return SegmentElement::find()
            ->id($segmentId)
            ->site('*')
            ->status(null)
            ->one();
    }

    /**
     * Returns segments by IDs.
     *
     * @param int[]|null $segmentIds
     * @return SegmentElement[]
     */
    public function getSegmentsByIds(?array $segmentIds): array
    {
        if (empty($segmentIds)) {
            return [];
        }

        /** @var SegmentElement[] */
        return SegmentElement::find()
            ->id($segmentIds)
            ->site('*')
            ->status(null)
            ->fixedOrder()
            ->all();
    }

    /**
     * Returns all segments.
     *
     * @return SegmentElement[]
     */
    public function getAllSegments(): array
    {
        /** @var SegmentElement[]] */
        return SegmentElement::find()
            ->site('*')
            ->all();
    }

    /**
     * Returns the segment's contacts.
     *
     * @return ContactElement[]
     */
    public function getContacts(SegmentElement $segment): array
    {
        return $this->getFilteredContacts($segment);
    }

    /**
     * Returns the segment's contact IDs.
     *
     * @return int[]
     */
    public function getContactIds(SegmentElement $segment): array
    {
        return $this->getFilteredContactIds($segment);
    }

    /**
     * Returns the segment's contacts filtered by the provided contact IDs.
     *
     * @return ContactElement[]
     */
    public function getFilteredContacts(SegmentElement $segment, array $contactIds = null): array
    {
        return $this->getFilteredContactQuery($segment, $contactIds)
            ->all();
    }

    /**
     * Returns the segment's contact IDs filtered by the provided contact IDs.
     *
     * @return int[]
     */
    public function getFilteredContactIds(SegmentElement $segment, array $contactIds = null): array
    {
        return $this->getFilteredContactQuery($segment, $contactIds)
            ->ids();
    }

    private function getFilteredContactQuery(SegmentElement $segment, array $contactIds = null): ContactElementQuery
    {
        $contactQuery = ContactElement::find();

        if ($contactIds !== null) {
            $contactQuery->id($contactIds);
        }

        $segment->getContactCondition()->modifyQuery($contactQuery);

        return $contactQuery;
    }
}
