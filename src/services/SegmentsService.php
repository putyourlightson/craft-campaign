<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\services;

use Craft;
use craft\base\Component;
use craft\helpers\Db;
use craft\records\Element_SiteSettings;
use craft\web\View;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\db\ContactElementQuery;
use putyourlightson\campaign\elements\SegmentElement;
use RuntimeException;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
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
        return $this->filterContacts($segment, null);
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
        return $this->filterContactIds($segment, null);
    }

    /**
     * Filters the segment's contacts by the contact IDs
     *
     * @param SegmentElement $segment
     * @param int[]|null $contactIds
     *
     * @return ContactElement[]
     */
    public function filterContacts(SegmentElement $segment, array $contactIds = null): array
    {
        $filteredContacts = [];
        $contactQuery = $this->_getContactQuery($contactIds);

        if ($segment->segmentType == 'regular') {
            $filteredContacts = $contactQuery
                ->where($this->_getConditions($segment))
                ->all();
        }

        else if ($segment->segmentType == 'template') {
            $contacts = $contactQuery->all();

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
     * Filters the segment's contact IDs by the contact IDs
     *
     * @param SegmentElement $segment
     * @param int[]|null $contactIds
     *
     * @return ContactElement[]
     */
    public function filterContactIds(SegmentElement $segment, array $contactIds = null): array
    {
        $filteredContactIds = [];
        $contactQuery = $this->_getContactQuery($contactIds);

        if ($segment->segmentType == 'regular') {
            $filteredContactIds = $contactQuery
                ->where($this->_getConditions($segment))
                ->ids();
        }

        else if ($segment->segmentType == 'template') {
            $contacts = $this->filterContacts($segment, $contactIds);

            foreach ($contacts as $contact) {
                $filteredContactIds[] = $contact->id;
            }
        }

        return $filteredContactIds;
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

        foreach ($segment->conditions as $andCondition) {
            $condition = ['or'];

            /* @var array $andCondition */
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
    private function _getContactQuery(array $contactIds = null)
    {
        $contactQuery = ContactElement::find();

        if ($contactIds !== null) {
            $contactQuery->id($contactIds);
        }

        return $contactQuery;
    }
}
