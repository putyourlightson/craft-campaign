<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\controllers;

use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\helpers\LogHelper;
use putyourlightson\campaign\models\ExportModel;

use Craft;
use craft\behaviors\FieldLayoutBehavior;
use craft\helpers\FileHelper;
use craft\web\Controller;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

/**
 * ExportsController
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 */
class ExportsController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     * @throws ForbiddenHttpException
     */
    public function init()
    {
        // Require permission
        $this->requirePermission('campaign-export');
    }

    /**
     * @param ExportModel|null $export The export, if there were any validation errors.
     *
     * @return Response
     * @throws InvalidConfigException
     */
    public function actionNewExport(ExportModel $export = null): Response
    {
        if ($export === null) {
            $export = new ExportModel();
        }

        $variables = [];
        $variables['export'] = $export;

        // Mailing list element selector variables
        $variables['mailingListElementType'] = MailingListElement::class;

        // Get contact fields
        /** @var FieldLayoutBehavior $fieldLayoutBehavior */
        $fieldLayoutBehavior = Campaign::$plugin->getSettings()->getBehavior('contactFieldLayout');
        $fieldLayout = $fieldLayoutBehavior->getFieldLayout();
        $variables['fields'] = $fieldLayout->getFields();

        // Render the template
        return $this->renderTemplate('campaign/import-export/export', $variables);
    }

    /**
     * Exports a file
     *
     * @return Response|null
     * @throws Exception
     * @throws BadRequestHttpException
     * @throws \Throwable
     */
    public function actionExportFile()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $export = new ExportModel();
        $export->mailingListIds = $request->getBodyParam('mailingListIds');

        // Get fields to export
        $export->fields = [];
        $fields = $request->getBodyParam('fields');
        if (\is_array($fields)) {
            foreach ($fields as $field => $value) {
                if ($value) {
                    $export->fields[] = $field;
                }
            }
        }

        // Get storage directory path
        $path = Craft::$app->path->getStoragePath().'/campaign/exports/'.gmdate('YmdHis').'/';

        // Create directory
        FileHelper::createDirectory($path);

        // Set file path
        $export->filePath = $path.'export.csv';

        // Validate it
        if ($export->validate() === false) {
            Craft::$app->getSession()->setError(Craft::t('campaign', 'Couldn’t export file.'));

            // Send the export back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'export' => $export
            ]);

            return null;
        }

        // Export it
        if (!Campaign::$plugin->exports->exportFile($export)) {
            Craft::$app->getSession()->setError(Craft::t('campaign', 'Couldn’t export file.'));

            // Send the export back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'export' => $export
            ]);

            return null;
        }

        // Log it
        LogHelper::logUserAction('File exported by "{username}".', [], __METHOD__);

        // Call for max power
        Campaign::$plugin->maxPowerLieutenant();

        return Craft::$app->getResponse()->sendFile($export->filePath);
    }
}