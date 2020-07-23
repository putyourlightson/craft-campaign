<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\controllers;

use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\models\ExportModel;

use Craft;
use craft\behaviors\FieldLayoutBehavior;
use craft\helpers\FileHelper;
use craft\web\Controller;
use Throwable;
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
        $this->requirePermission('campaign:exportContacts');

        parent::init();
    }

    /**
     * @param string|null $siteHandle
     * @param ExportModel|null $export The export, if there were any validation errors.
     *
     * @return Response
     * @throws InvalidConfigException
     */
    public function actionIndex(string $siteHandle = null, ExportModel $export = null): Response
    {
        if ($export === null) {
            $export = new ExportModel();
        }

        $variables = [];
        $variables['export'] = $export;

        // Set the current site to the site handle if set
        if ($siteHandle !== null) {
            $site = Craft::$app->getSites()->getSiteByHandle($siteHandle);

            if ($site !== null) {
                Craft::$app->getSites()->setCurrentSite($site);
            }
        }

        // Mailing list element selector variables
        $variables['mailingListElementType'] = MailingListElement::class;

        // Get contact fields
        $variables['fields'] = Campaign::$plugin->getSettings()->getContactFields();

        // Render the template
        return $this->renderTemplate('campaign/contacts/export', $variables);
    }

    /**
     * Exports a file
     *
     * @return Response|null
     * @throws Exception
     * @throws BadRequestHttpException
     * @throws Throwable
     */
    public function actionExportFile()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $export = new ExportModel();
        $export->mailingListIds = $request->getBodyParam('mailingListIds');
        $export->subscribedDate = $request->getBodyParam('subscribedDate');

        // Get fields to export
        $export->fields = [];
        $fields = $request->getBodyParam('fields');
        if (is_array($fields)) {
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
        Campaign::$plugin->log('File exported by "{username}".');

        return Craft::$app->getResponse()->sendFile($export->filePath);
    }
}
