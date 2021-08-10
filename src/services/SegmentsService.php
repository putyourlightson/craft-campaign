<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\services;

use Craft;
use craft\base\Component;
use craft\base\FieldInterface;
use craft\helpers\Db;
use craft\records\Element_SiteSettings;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\db\ContactElementQuery;
use putyourlightson\campaign\elements\SegmentElement;
use putyourlightson\campaign\helpers\SegmentHelper;
use Twig\Error\LoaderError;
use Twig\Error\SyntaxError;

/**
 * SegmentsService
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.9.0
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

    /**
     * Returns the segment's contacts
     *
     * @param SegmentElement $segment
     *
     * @return ContactElement[]
     */
    public function getContacts(SegmentElement $segment): array
    {
        return $this->getFilteredContacts($segment);
    }

    /**
     * Returns the segment's contact IDs
     *
     * @param SegmentElement $segment
     *
     * @return int[]
     */
    public function getContactIds(SegmentElement $segment): array
    {
        return $this->getFilteredContactIds($segment);
    }

    /**
     * Returns the segment's contacts filtered by the provided contact IDs
     *
     * @param SegmentElement $segment
     * @param int[]|null $contactIds
     *
     * @return ContactElement[]
     */
    public function getFilteredContacts(SegmentElement $segment, array $contactIds = null): array
    {
        $filteredContacts = [];
        $contactElementQuery = $this->_getContactElementQuery($contactIds);

        if ($segment->segmentType == 'regular') {
            $filteredContacts = $contactElementQuery
                ->where($this->_getConditions($segment))
                ->all();
        }
        elseif ($segment->segmentType == 'template') {
            $contacts = $contactElementQuery->all();

            foreach ($contacts as $contact) {
                try {
                    $rendered = Craft::$app->getView()->renderString($segment->conditions, [
                        'contact' => $contact,
                    ]);

                    // Convert rendered value to boolean
                    $evaluated = (bool)trim($rendered);

                    if ($evaluated) {
                        $filteredContacts[] = $contact;
                    }
                }
                catch (LoaderError $e) {}
                catch (SyntaxError $e) {}
            }
        }

        return $filteredContacts;
    }

    /**
     * Returns the segment's contact IDs filtered by the provided contact IDs
     *
     * @param SegmentElement $segment
     * @param int[]|null $contactIds
     *
     * @return int[]
     */
    public function getFilteredContactIds(SegmentElement $segment, array $contactIds = null): array
    {
        $filteredContactIds = [];
        $contactElementQuery = $this->_getContactElementQuery($contactIds);

        if ($segment->segmentType == 'regular') {
            $filteredContactIds = $contactElementQuery->where($this->_getConditions($segment))->ids();
        }

        elseif ($segment->segmentType == 'template') {
            $contacts = $this->getFilteredContacts($segment, $contactIds);

            foreach ($contacts as $contact) {
                $filteredContactIds[] = $contact->id;
            }
        }

        return $filteredContactIds;
    }

    public function updateField(FieldInterface $field)
    {
        if (!SegmentHelper::isContactField($field)) {
            return;
        }

        $newFieldColumn = SegmentHelper::fieldColumnFromField($field);
        $oldFieldColumn = SegmentHelper::oldFieldColumnFromField($field);

        if ($newFieldColumn == $oldFieldColumn) {
            return;
        }

        $modified = false;

        $segments = SegmentElement::find()
            ->status(null)
            ->all();

        foreach ($segments as $segment) {
            foreach ($segment->conditions as &$andCondition) {
                foreach ($andCondition as &$orCondition) {
                    if ($orCondition[1] == $oldFieldColumn) {
                        $orCondition[1] = $newFieldColumn;
                        $modified = true;
                    }
                }
            }

            if ($modified) {
                Craft::$app->elements->saveElement($segment);
            }
        }
    }

    public function deleteField(FieldInterface $field)
    {
        if (!SegmentHelper::isContactField($field)) {
            return;
        }

        $modified = false;

        $segments = SegmentElement::find()
            ->status(null)
            ->all();

        foreach ($segments as $segment) {
            $fieldColumn = SegmentHelper::fieldColumnFromField($field);

            foreach ($segment->conditions as &$andCondition) {
                foreach ($andCondition as $key => $orCondition) {
                    if ($orCondition[1] == $fieldColumn) {
                        unset($andCondition[$key]);
                        $modified = true;
                    }
                }
            }

            if ($modified) {
                Craft::$app->elements->saveElement($segment);
            }
        }
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns the conditions
     *
     * @param SegmentElement $segment
     *
     * @return array[]
     */
    private function _getConditions(SegmentElement $segment): array
    {
        $conditions = ['and'];

        /** @var array $andCondition */
        foreach ($segment->conditions as $andCondition) {
            $condition = ['or'];

            foreach ($andCondition as $orCondition) {
                // Exclude template conditions
                if ($orCondition[1] == 'template') {
                    continue;
                }

                $operator = $orCondition[0];

                // If operator contains %v
                if (strpos($operator, '%v') !== false) {
                    $orCondition[0] = trim(str_replace('%v', '', $orCondition[0]));
                    $orCondition[2] = '%'.$orCondition[2];
                    $orCondition[3] = false;
                }

                // If operator contains v%
                if (strpos($operator, 'v%') !== false) {
                    $orCondition[0] = trim(str_replace('v%', '', $orCondition[0]));
                    $orCondition[2] .= '%';
                    $orCondition[3] = false;
                }

                // Convert value if is a date
                if (preg_match('/\d{1,2}\/\d{1,2}\/\d{4}/', $orCondition[2])) {
                    $orCondition[2] = Db::prepareDateForDb(['date' => $orCondition[2]]) ?? '';
                }

                $condition[] = $orCondition;
            }

            $conditions[] = $condition;
        }

        return $conditions;
    }

    /**
     * @param int[]|null $contactIds
     *
     * @return ContactElementQuery
     */
    private function _getContactElementQuery(array $contactIds = null)
    {
        $contactQuery = ContactElement::find();

        if ($contactIds !== null) {
            $contactQuery->id($contactIds);
        }

        return $contactQuery;
    }
}
