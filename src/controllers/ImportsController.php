<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\controllers;

use Craft;
use craft\elements\Asset;
use craft\helpers\App;
use craft\helpers\Assets;
use craft\helpers\FileHelper;
use craft\web\Controller;
use craft\web\UploadedFile;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\models\ImportModel;
use yii\web\BadRequestHttpException;
use yii\web\Response;

class ImportsController extends Controller
{
    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        // Require permission
        $this->requirePermission('campaign:importContacts');

        return parent::beforeAction($action);
    }

    /**
     * Default action.
     */
    public function actionIndex(string $siteHandle = null): ?Response
    {
        // Set the current site to the site handle if set
        if ($siteHandle !== null) {
            $site = Craft::$app->getSites()->getSiteByHandle($siteHandle);

            if ($site !== null) {
                Craft::$app->getSites()->setCurrentSite($site);
            }
        }

        // Render the template
        return $this->renderTemplate('campaign/contacts/import');
    }

    /**
     * Uploads a file.
     *
     * @param ImportModel|null $import The import, if there were any validation errors.
     */
    public function actionUploadFile(ImportModel $import = null): ?Response
    {
        $this->requirePostRequest();

        // Get the uploaded file
        $file = UploadedFile::getInstanceByName('file');

        if ($file === null) {
            return $this->asFailure(Craft::t('campaign', 'A CSV file must be selected to upload.'));
        }

        $tempFilePath = $file->saveAsTempFile();

        // Ensure file is a CSV file
        $mimeType = FileHelper::getMimeType($tempFilePath);

        if ($mimeType != 'text/plain' && $mimeType != 'text/csv' && $mimeType != 'application/csv') {
            return $this->asFailure(Craft::t('campaign', 'The file you selected to upload must be a CSV file.'));
        }

        // Copy to user temporary folder
        $userTemporaryFolder = Craft::$app->getAssets()->getUserTemporaryUploadFolder();

        $fileName = Assets::prepareAssetName($file->name);

        // Force allow CSV file extension
        // https://github.com/putyourlightson/craft-campaign/issues/234
        Craft::$app->getConfig()->getGeneral()->allowedFileExtensions[] = 'csv';

        $asset = new Asset();
        $asset->tempFilePath = $tempFilePath;
        $asset->filename = $fileName;
        $asset->newFolderId = $userTemporaryFolder->id;
        $asset->setVolumeId($userTemporaryFolder->volumeId);
        $asset->uploaderId = Craft::$app->getUser()->getId();
        $asset->avoidFilenameConflicts = true;
        $asset->setScenario(Asset::SCENARIO_CREATE);

        if (!Craft::$app->getElements()->saveElement($asset)) {
            return $this->asFailure(Craft::t('campaign', 'Unable to upload CSV file.'));
        }

        if ($import === null) {
            $import = new ImportModel();
        }

        $import->assetId = $asset->id;
        $import->fileName = $fileName;

        return $this->returnFieldsTemplate($import);
    }

    /**
     * Imports a file.
     */
    public function actionImportFile(): ?Response
    {
        $this->requirePostRequest();

        $import = $this->getImportModelFromParams();
        $import->assetId = $this->request->getRequiredBodyParam('assetId');
        $import->fileName = $this->request->getRequiredBodyParam('fileName');

        $mailingListIds = $this->request->getBodyParam('mailingListIds');
        $import->mailingListId = $mailingListIds[0] ?? null;

        if (!$import->validate()) {
            $errors = implode('. ', $import->getErrorSummary(true));
            Campaign::$plugin->log('Couldn’t import file. {errors}', [
                'errors' => $errors,
            ]);

            Craft::$app->getSession()->setError(Craft::t('campaign', 'Couldn’t import file.'));

            // Send the import back to the fields template
            return $this->returnFieldsTemplate($import, $mailingListIds);
        }

        // Save and queue an import for each mailing list
        foreach ($mailingListIds as $mailingListId) {
            $import->id = null;
            $import->mailingListId = $mailingListId;

            if (Campaign::$plugin->imports->saveImport($import)) {
                Campaign::$plugin->imports->queueImport($import);
            }
        }

        Campaign::$plugin->log('CSV file "{fileName}" imported by “{username}”.', [
            'fileName' => $import->fileName,
        ]);

        return $this->asModelSuccess($import, Craft::t('campaign', 'CSV file successfully queued for importing.'), 'import');
    }

    /**
     * Selects a user group.
     *
     * @param ImportModel|null $import The import, if there were any validation errors.
     */
    public function actionSelectUserGroup(ImportModel $import = null): ?Response
    {
        $this->requirePostRequest();

        // Get the user group ID
        $userGroupId = $this->request->getRequiredBodyParam('userGroupId');

        if ($userGroupId === null) {
            throw new BadRequestHttpException('User group is required.');
        }

        // Get user group
        $userGroup = Craft::$app->getUserGroups()->getGroupById($userGroupId);

        if ($userGroup === null) {
            throw new BadRequestHttpException('User group not found.');
        }

        if ($import === null) {
            $import = new ImportModel();
        }

        $import->userGroupId = $userGroupId;

        return $this->returnFieldsTemplate($import);
    }

    /**
     * Imports a user group.
     */
    public function actionImportUserGroup(): ?Response
    {
        $this->requirePostRequest();

        $import = $this->getImportModelFromParams('field_');
        $import->userGroupId = $this->request->getRequiredBodyParam('userGroupId');

        $mailingListIds = $this->request->getBodyParam('mailingListIds');
        $import->mailingListId = $mailingListIds[0] ?? null;

        if (!$import->validate()) {
            $errors = implode('. ', $import->getErrorSummary(true));
            Campaign::$plugin->log('Couldn’t import user group. {errors}', [
                'errors' => $errors,
            ]);

            Craft::$app->getSession()->setError(Craft::t('campaign', 'Couldn’t import user group.'));

            // Send the import back to the fields template
            return $this->returnFieldsTemplate($import);
        }

        // Save and queue an import for each mailing list
        foreach ($mailingListIds as $mailingListId) {
            $import->id = null;
            $import->mailingListId = $mailingListId;

            if (Campaign::$plugin->imports->saveImport($import)) {
                Campaign::$plugin->imports->queueImport($import);
            }
        }

        Campaign::$plugin->log('User group "{userGroup}" imported by “{username}”.', [
            'userGroup' => $import->getUserGroup()->name,
        ]);

        return $this->asModelSuccess($import, Craft::t('campaign', 'User group successfully queued for importing.'), 'import');
    }

    /**
     * Downloads a file.
     */
    public function actionDownloadFile(): ?Response
    {
        $importId = $this->request->getRequiredParam('importId');

        $import = Campaign::$plugin->imports->getImportById($importId);

        if ($import == null) {
            throw new BadRequestHttpException('Import not found.');
        }

        App::maxPowerCaptain();

        $handle = Campaign::$plugin->imports->getHandle($import);

        if ($handle == null) {
            throw new BadRequestHttpException('Imported file not found.');
        }

        return $this->response->sendStreamAsFile($handle, $import->fileName);
    }

    /**
     * Deletes an import.
     */
    public function actionDelete(): ?Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $importId = $this->request->getRequiredBodyParam('id');
        Campaign::$plugin->imports->deleteImportById($importId);

        return $this->asSuccess();
    }

    /**
     * Returns the `fields` template.
     */
    private function returnFieldsTemplate(ImportModel $import, array|string|null $mailingListIds = []): Response
    {
        // Set the current site to the site handle if set
        $siteHandle = $this->request->getSegment(4);

        if ($siteHandle !== null) {
            $site = Craft::$app->getSites()->getSiteByHandle($siteHandle);

            if ($site !== null) {
                Craft::$app->getSites()->setCurrentSite($site);
            }
        }

        $variables = [
            'import' => $import,
            'mailingListElementType' => MailingListElement::class,
            'mailingLists' => [],
            'emailFieldLabel' => Campaign::$plugin->settings->getEmailFieldLabel(),
            'fields' => Campaign::$plugin->settings->getContactFields(),
            'columns' => Campaign::$plugin->imports->getColumns($import),
        ];

        if (is_array($mailingListIds)) {
            foreach ($mailingListIds as $mailingListId) {
                $mailingList = Campaign::$plugin->mailingLists->getMailingListById($mailingListId);

                if ($mailingList !== null) {
                    $variables['mailingLists'][] = $mailingList;
                }
            }
        }

        return $this->renderTemplate('campaign/contacts/import/_fields', $variables);
    }

    private function getImportModelFromParams(string $fieldIndexPrefix = null): ImportModel
    {
        $import = new ImportModel();

        $import->unsubscribe = (bool)$this->request->getBodyParam('unsubscribe');
        $import->forceSubscribe = (bool)$this->request->getBodyParam('forceSubscribe');
        $import->emailFieldIndex = $this->request->getBodyParam('emailFieldIndex');
        $import->fieldIndexes = $this->request->getBodyParam('fieldIndexes');

        if (is_array($import->fieldIndexes) && $fieldIndexPrefix) {
            foreach ($import->fieldIndexes as $key => $fieldIndex) {
                if ($fieldIndex != 'fullName' && $fieldIndex != 'firstName' && $fieldIndex != 'lastName') {
                    $import->fieldIndexes[$key] = $fieldIndexPrefix . $fieldIndex;
                }
            }
        }

        return $import;
    }
}
