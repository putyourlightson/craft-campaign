<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\controllers;

use Craft;
use craft\helpers\FileHelper;
use craft\web\Controller;

use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\models\ExportModel;
use yii\web\Response;

class ExportsController extends Controller
{
    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        // Require permission
        $this->requirePermission('campaign:exportContacts');

        return parent::beforeAction($action);
    }

    /**
     * Main export page.
     */
    public function actionIndex(string $siteHandle = null, ExportModel $export = null): Response
    {
        if ($export === null) {
            $export = new ExportModel();
        }

        // Set the current site to the site handle if set
        if ($siteHandle !== null) {
            $site = Craft::$app->getSites()->getSiteByHandle($siteHandle);

            if ($site !== null) {
                Craft::$app->getSites()->setCurrentSite($site);
            }
        }

        $variables = [
            'export' => $export,
            'mailingListElementType' => MailingListElement::class,
            'fields' => Campaign::$plugin->getSettings()->getContactFields(),
        ];

        // Render the template
        return $this->renderTemplate('campaign/contacts/export', $variables);
    }

    /**
     * Exports a file.
     */
    public function actionExportFile(): ?Response
    {
        $this->requirePostRequest();

        $export = new ExportModel();
        $export->mailingListIds = $this->request->getBodyParam('mailingListIds');
        $export->subscribedDate = $this->request->getBodyParam('subscribedDate');

        // Get fields to export
        $export->fields = [];
        $fields = $this->request->getBodyParam('fields');
        if (is_array($fields)) {
            foreach ($fields as $field => $value) {
                if ($value) {
                    $export->fields[] = $field;
                }
            }
        }

        // Get storage directory path
        $path = Craft::$app->path->getStoragePath() . '/campaign/exports/' . gmdate('YmdHis') . '/';

        // Create directory
        FileHelper::createDirectory($path);

        // Set file path
        $export->filePath = $path . 'export.csv';

        // Validate it
        if ($export->validate() === false) {
            Craft::$app->getSession()->setError(Craft::t('campaign', 'Couldn’t export file.'));

            // Send the export back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'export' => $export,
            ]);

            return null;
        }

        // Export it
        if (!Campaign::$plugin->exports->exportFile($export)) {
            Craft::$app->getSession()->setError(Craft::t('campaign', 'Couldn’t export file.'));

            // Send the export back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'export' => $export,
            ]);

            return null;
        }

        // Log it
        Campaign::$plugin->log('File exported by "{username}".');

        return Craft::$app->getResponse()->sendFile($export->filePath);
    }
}
