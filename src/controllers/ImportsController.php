<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\controllers;

use craft\elements\Asset;
use craft\helpers\Assets;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\models\ImportModel;

use Craft;
use craft\web\Controller;
use craft\helpers\FileHelper;
use craft\web\UploadedFile;
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
            Craft::$app->getSession()->setError(Craft::t('campaign', 'A CSV file must be selected to upload.'));

            return null;
        }

        $tempFilePath = $file->saveAsTempFile();

        // Ensure file is a CSV file
        $mimeType = FileHelper::getMimeType($tempFilePath);

        if ($mimeType != 'text/plain' && $mimeType != 'text/csv' && $mimeType != 'application/csv') {
            Craft::$app->getSession()->setError(Craft::t('campaign', 'The file you selected to upload must be a CSV file.'));

            return null;
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
            Craft::$app->getSession()->setError(Craft::t('campaign', 'Unable to upload CSV file.'));

            return null;
        }

        if ($import === null) {
            $import = new ImportModel();
        }

        $import->assetId = $asset->id;
        $import->fileName = $fileName;

        return $this->_returnFieldsTemplate($import);
    }

    /**
     * Imports a file.
     */
    public function actionImportFile(): ?Response
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();

        $import = new ImportModel();
        $import->assetId = $request->getRequiredBodyParam('assetId');
        $import->fileName = $request->getRequiredBodyParam('fileName');

        $mailingListIds = $request->getBodyParam('mailingListIds');
        $import->mailingListId = $mailingListIds[0] ?? '';

        $import->forceSubscribe = (bool)$request->getBodyParam('forceSubscribe');

        // Get email and custom field indexes
        $import->emailFieldIndex = $request->getBodyParam('emailFieldIndex');
        $import->fieldIndexes = $request->getBodyParam('fieldIndexes');

        // Validate it
        if (!$import->validate()) {
            $errors = implode('. ', $import->getErrorSummary(true));
            Campaign::$plugin->log('Couldn’t import file. {errors}', ['errors' => $errors]);

            Craft::$app->getSession()->setError(Craft::t('campaign', 'Couldn’t import file.'));

            // Send the import back to the fields template
            return $this->_returnFieldsTemplate($import, $mailingListIds);
        }

        // Save and queue an import for each mailing list
        foreach ($mailingListIds as $mailingListId) {
            $import->id = null;
            $import->mailingListId = $mailingListId;

            if (Campaign::$plugin->imports->saveImport($import)) {
                Campaign::$plugin->imports->queueImport($import);
            }
        }

        Campaign::$plugin->log('CSV file "{fileName}" imported by "{username}".', ['fileName' => $import->fileName]);

        Craft::$app->getSession()->setNotice(Craft::t('campaign', 'CSV file successfully queued for importing.'));

        return $this->redirectToPostedUrl($import);
    }

    /**
     * Selects a user group.
     *
     * @param ImportModel|null $import The import, if there were any validation errors.
     */
    public function actionSelectUserGroup(ImportModel $import = null): ?Response
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();

        // Get the user group ID
        $userGroupId = $request->getRequiredBodyParam('userGroupId');

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

        return $this->_returnFieldsTemplate($import);
    }

    /**
     * Imports a user group.
     */
    public function actionImportUserGroup(): ?Response
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();

        $import = new ImportModel();
        $import->userGroupId = $request->getRequiredBodyParam('userGroupId');

        $mailingListIds = $request->getBodyParam('mailingListIds');
        $import->mailingListId = $mailingListIds[0] ?? '';

        $import->forceSubscribe = (bool)$request->getBodyParam('forceSubscribe');

        // Get core fields and custom field indexes
        $import->emailFieldIndex = $request->getBodyParam('emailFieldIndex');
        $import->fieldIndexes = $request->getBodyParam('fieldIndexes', []);

        // Prepend `field_` to each custom field index
        foreach ($import->fieldIndexes as $key => $fieldIndex) {
            if ($fieldIndex != 'firstName' && $fieldIndex != 'lastName') {
                $import->fieldIndexes[$key] = 'field_'.$fieldIndex;
            }
        }

        // Validate it
        if (!$import->validate()) {
            $errors = implode('. ', $import->getErrorSummary(true));
            Campaign::$plugin->log('Couldn’t import user group. {errors}', ['errors' => $errors]);

            Craft::$app->getSession()->setError(Craft::t('campaign', 'Couldn’t import user group.'));

            // Send the import back to the fields template
            return $this->_returnFieldsTemplate($import);
        }

        // Save and queue an import for each mailing list
        foreach ($mailingListIds as $mailingListId) {
            $import->id = null;
            $import->mailingListId = $mailingListId;

            if (Campaign::$plugin->imports->saveImport($import)) {
                Campaign::$plugin->imports->queueImport($import);
            }
        }

        // Log it
        Campaign::$plugin->log('User group "{userGroup}" imported by "{username}".', ['userGroup' => $import->getUserGroup()->name]);

        Craft::$app->getSession()->setNotice(Craft::t('campaign', 'User group successfully queued for importing.'));

        return $this->redirectToPostedUrl($import);
    }

    /**
     * Downloads a file.
     */
    public function actionDownloadFile(): ?Response
    {
        $importId = Craft::$app->getRequest()->getRequiredParam('importId');

        $import = Campaign::$plugin->imports->getImportById($importId);

        if ($import == null) {
            throw new BadRequestHttpException('Import not found.');
        }

        // Call for max power
        Campaign::$plugin->maxPowerLieutenant();

        $handle = Campaign::$plugin->imports->getHandle($import);

        if ($handle == null) {
            throw new BadRequestHttpException('Imported file not found.');
        }

        return Craft::$app->getResponse()->sendStreamAsFile($handle, $import->fileName);
    }

    /**
     * Deletes an import.
     */
    public function actionDeleteImport(): ?Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $importId = Craft::$app->getRequest()->getRequiredBodyParam('id');

        Campaign::$plugin->imports->deleteImportById($importId);

        return $this->asJson(['success' => true]);
    }

    /**
     * Returns the fields template.
     */
    private function _returnFieldsTemplate(ImportModel $import, array|string|null $mailingListIds = []): ?Response
    {
        $variables = [];
        $variables['import'] = $import;

        // Set the current site to the site handle if set
        $siteHandle = Craft::$app->getRequest()->getSegment(4);

        if ($siteHandle !== null) {
            $site = Craft::$app->getSites()->getSiteByHandle($siteHandle);

            if ($site !== null) {
                Craft::$app->getSites()->setCurrentSite($site);
            }
        }

        // Mailing list element selector variables
        $variables['mailingListElementType'] = MailingListElement::class;

        // Get mailing lists
        $variables['mailingLists'] = [];

        if (is_array($mailingListIds)) {
            foreach ($mailingListIds as $mailingListId) {
                $mailingList = Campaign::$plugin->mailingLists->getMailingListById($mailingListId);

                if ($mailingList !== null) {
                    $variables['mailingLists'][] = $mailingList;
                }
            }
        }

        // Get contact fields
        $variables['fields'] = Campaign::$plugin->getSettings()->getContactFields();

        // Get columns
        $variables['columns'] = Campaign::$plugin->imports->getColumns($import);

        return $this->renderTemplate('campaign/contacts/import/_fields', $variables);
    }
}
