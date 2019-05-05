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
        $contactConditions = $this->_getContactConditions($segment);

        $contacts = ContactElement::find()
            ->where($contactConditions)
            ->all();

        // TODO: remove support for template conditions in version 1.9.0
        $templateConditions = $this->_getTemplateConditions($segment);

        if (count($templateConditions)) {
            Craft::$app->getDeprecator()->log('SegmentTemplateConditions', 'Segment template conditions have been deprecated due to inefficiency and will be removed in version 1.9.0.');

            $view = Craft::$app->getView();

            // Get template mode so we can reset later
            $templateMode = $view->getTemplateMode();

            // Set template mode to site
            $view->setTemplateMode(View::TEMPLATE_MODE_SITE);

            // Evaluate template conditions
            foreach ($templateConditions as $templateCondition) {
                foreach ($contacts as $key => $contact) {
                    $operand = (bool)$templateCondition[0];
                    $evaluatedTemplate = null;

                    try {
                        $renderedTemplate = $view->renderTemplate($templateCondition[2], [
                            'contact' => $contact,
                        ]);

                        // Convert rendered template to boolean
                        $evaluatedTemplate = (bool)trim($renderedTemplate);
                    }
                    catch (RuntimeException $e) {}
                    catch (LoaderError $e) {}
                    catch (RuntimeError $e) {}
                    catch (SyntaxError $e) {}

                    // Remove if evaluated template does not equal operand
                    if ($evaluatedTemplate === null OR $evaluatedTemplate !== $operand) {
                        unset($contacts[$key]);
                    }
                }
            }

            // Reset template mode
            $view->setTemplateMode($templateMode);
        }

        return $contacts;
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
        // TODO: remove support for template conditions in 1.9.0
        if (count($this->_getTemplateConditions($segment))) {
            $contactIds = [];

            foreach ($this->getContacts($segment) as $contact) {
                $contactIds[] = $contact->id;
            }

            return $contactIds;
        }

        $contactConditions = $this->_getContactConditions($segment);

        $contactIds = ContactElement::find()
            ->where($contactConditions)
            ->ids();

        return $contactIds;
    }

    // Public Methods
    // =========================================================================

    /**
     * Returns the contact conditions
     *
     * @param SegmentElement $segment
     *
     * @return array[]
     */
    private function _getContactConditions(SegmentElement $segment): array
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
     * Returns the template conditions
     *
     * @param SegmentElement $segment
     *
     * @return array[]
     */
    private function _getTemplateConditions(SegmentElement $segment): array
    {
        $conditions = [];

        foreach ($segment->conditions as $andCondition) {
            /* @var array $andCondition */
            foreach ($andCondition as $orCondition) {
                if ($orCondition[1] == 'template') {
                    $conditions[] = $orCondition;
                }
            }
        }

        return $conditions;
    }
}
