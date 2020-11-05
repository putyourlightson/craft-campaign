<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\controllers;

use craft\errors\MissingComponentException;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\models\ImportModel;

use Craft;
use craft\web\Controller;
use craft\helpers\FileHelper;
use craft\web\UploadedFile;
use Throwable;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

/**
 * ImportsController
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 */
class ImportsController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     * @throws ForbiddenHttpException
     */
    public function init()
    {
        parent::init();

        // Require permission
        $this->requirePermission('campaign:importContacts');
    }

    /**
     * @param string|null $siteHandle
     *
     * @return Response
     */
    public function actionIndex(string $siteHandle = null): Response
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
     * Uploads a file
     *
     * @param ImportModel|null $import The import, if there were any validation errors.
     *
     * @return Response|null
     * @throws BadRequestHttpException
     * @throws Exception
     * @throws InvalidConfigException
     * @throws MissingComponentException
     */
    public function actionUploadFile(ImportModel $import = null)
    {
        $this->requirePostRequest();

        // Get the uploaded file
        $file = UploadedFile::getInstanceByName('file');

        if ($file === null) {
            Craft::$app->getSession()->setError(Craft::t('campaign', 'A CSV file must be selected to upload.'));

            return null;
        }

        // Get storage directory path
        $path = Craft::$app->path->getStoragePath().'/campaign/imports/'.gmdate('YmdHis').'/';

        // Create directory
        FileHelper::createDirectory($path);

        // Sanitize file name
        $fileName = FileHelper::sanitizeFilename($file->name);

        // Get file path
        $filePath = $path.$fileName;

        // Save file
        $file->saveAs($filePath);

        // Ensure file is a CSV file
        $mimeType = FileHelper::getMimeType($filePath);
        if ($mimeType != 'text/plain' && $mimeType != 'text/csv') {
            Craft::$app->getSession()->setError(Craft::t('campaign', 'The file you selected to upload must be a CSV file.'));

            return null;
        }

        if ($import === null) {
            $import = new ImportModel();
        }

        $import->fileName = $fileName;
        $import->filePath = $filePath;

        return $this->_returnFieldsTemplate($import);
    }

    /**
     * Imports a file
     *
     * @return Response|null
     * @throws BadRequestHttpException
     */
    public function actionImportFile()
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();

        $import = new ImportModel();
        $import->fileName = $request->getRequiredBodyParam('fileName');
        $import->filePath = $request->getRequiredBodyParam('filePath');

        $mailingListIds = $request->getBodyParam('mailingListIds');
        $import->mailingListId = $mailingListIds[0] ?? '';

        $import->forceSubscribe = $request->getBodyParam('forceSubscribe');

        // Get email and custom field indexes
        $import->emailFieldIndex = $request->getBodyParam('emailFieldIndex');
        $import->fieldIndexes = $request->getBodyParam('fieldIndexes');

        // Validate it
        if (!$import->validate()) {
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

        // Log it
        Campaign::$plugin->log('CSV file "{fileName}" imported by "{username}".', ['fileName' => $import->fileName]);

        Craft::$app->getSession()->setNotice(Craft::t('campaign', 'CSV file successfully queued for importing.'));

        return $this->redirectToPostedUrl($import);
    }

    /**
     * Select user group
     *
     * @param ImportModel|null $import The import, if there were any validation errors.
     *
     * @return Response|null
     * @throws BadRequestHttpException
     * @throws Exception
     */
    public function actionSelectUserGroup(ImportModel $import = null)
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
     * Imports a user group
     *
     * @return Response|null
     * @throws BadRequestHttpException
     */
    public function actionImportUserGroup()
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();

        $import = new ImportModel();
        $import->userGroupId = $request->getRequiredBodyParam('userGroupId');

        $mailingListIds = $request->getBodyParam('mailingListIds');
        $import->mailingListId = $mailingListIds[0] ?? '';

        $import->forceSubscribe = $request->getBodyParam('forceSubscribe');

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
     * Downloads a file
     *
     * @return Response|null
     * @throws BadRequestHttpException
     */
    public function actionDownloadFile()
    {
        $importId = Craft::$app->getRequest()->getRequiredParam('importId');

        $import = Campaign::$plugin->imports->getImportById($importId);

        if ($import == null || !file_exists($import->filePath)) {
            throw new BadRequestHttpException('Import not found.');
        }

        // Call for max power
        Campaign::$plugin->maxPowerLieutenant();

        return Craft::$app->getResponse()->sendFile($import->filePath, $import->fileName);
    }

    /**
     * Deletes an import
     *
     * @return Response
     * @throws BadRequestHttpException
     * @throws Throwable
     */
    public function actionDeleteImport(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $importId = Craft::$app->getRequest()->getRequiredBodyParam('id');

        Campaign::$plugin->imports->deleteImportById($importId);

        return $this->asJson(['success' => true]);
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns the fields template
     *
     * @param ImportModel $import
     * @param array|string|null $mailingListIds
     *
     * @return Response
     */
    private function _returnFieldsTemplate(ImportModel $import, $mailingListIds = []): Response
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
