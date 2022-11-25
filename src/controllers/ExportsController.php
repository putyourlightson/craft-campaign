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
            'emailFieldLabel' => Campaign::$plugin->settings->getEmailFieldLabel(),
            'fields' => Campaign::$plugin->settings->getContactFields(),
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

        $export = new ExportModel([
            'mailingListIds' => $this->request->getBodyParam('mailingListIds'),
            'fields' => $this->request->getBodyParam('fields'),
        ]);

        // Get storage directory path
        $path = Craft::$app->getPath()->getStoragePath() . '/campaign/exports/' . gmdate('YmdHis') . '/';

        // Create directory
        FileHelper::createDirectory($path);

        // Set file path
        $export->filePath = $path . 'export.csv';

        // Export it
        if (!Campaign::$plugin->exports->exportFile($export)) {
            return $this->asModelFailure($export, Craft::t('campaign', 'Couldn’t export file.'), 'export');
        }

        // Log it
        Campaign::$plugin->log('File exported by "{username}".');

        return $this->response->sendFile($export->filePath);
    }
}
