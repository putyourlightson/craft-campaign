<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\services;

use Craft;
use craft\helpers\Db;
use craft\records\Element_SiteSettings;
use craft\web\View;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\SegmentElement;

use craft\base\Component;
use yii\base\Exception;

/**
 * SegmentsService
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
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
        $templateConditions = [];
        $condition = ['and'];

        foreach ($segment->conditions as $andCondition) {
            $conditions = ['or'];

            /* @var array $andCondition */
            foreach ($andCondition as $orCondition) {
                // Deal with template conditions later
                if ($orCondition[1] == 'template') {
                    $templateConditions[] = $orCondition;
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

                $conditions[] = $orCondition;
            }

            $condition[] = $conditions;
        }

        $contacts = ContactElement::find()
            ->where($condition)
            ->all();

        if (count($templateConditions)) {
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
                    catch (\Twig_Error_Loader $e) {}
                    catch (Exception $e) {}

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
}