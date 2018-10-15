<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\controllers;

use craft\behaviors\FieldLayoutBehavior;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\models\ImportModel;

use Craft;
use craft\web\Controller;
use craft\helpers\FileHelper;
use craft\web\UploadedFile;
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
        $variables = [];

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
     * @throws \craft\errors\MissingComponentException
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
        if ($mimeType != 'text/plain' AND $mimeType != 'text/csv') {
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
     * @throws InvalidConfigException
     */
    public function actionImportFile()
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();

        $import = new ImportModel();
        $import->fileName = $request->getRequiredBodyParam('fileName');
        $import->filePath = $request->getRequiredBodyParam('filePath');

        $mailingListId = $request->getBodyParam('mailingListId');
        $import->mailingListId = (\is_array($mailingListId) AND isset($mailingListId[0])) ? $mailingListId[0] : '';

        // Get email and custom field indexes
        $import->emailFieldIndex = $request->getBodyParam('emailFieldIndex');
        $import->fieldIndexes = $request->getBodyParam('fieldIndexes');

        // Save it
        if (!Campaign::$plugin->imports->saveImport($import)) {
            Craft::$app->getSession()->setError(Craft::t('campaign', 'Couldn’t import file.'));

            // Send the import back to the fields template
            return $this->_returnFieldsTemplate($import);
        }

        Campaign::$plugin->imports->queueImport($import);

        // Log it
        Campaign::$plugin->logUserAction('CSV file "{fileName}" imported by "{username}".', ['fileName' => $import->fileName], __METHOD__);

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
     * @throws InvalidConfigException
     */
    public function actionImportUserGroup()
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();

        $import = new ImportModel();
        $import->userGroupId = $request->getRequiredBodyParam('userGroupId');

        $mailingListId = $request->getBodyParam('mailingListId');
        $import->mailingListId = $mailingListId[0] ?? '';

        // Get email and custom field indexes
        $import->emailFieldIndex = $request->getBodyParam('emailFieldIndex');
        $import->fieldIndexes = $request->getBodyParam('fieldIndexes');

        // Save it
        if (!Campaign::$plugin->imports->saveImport($import)) {
            Craft::$app->getSession()->setError(Craft::t('campaign', 'Couldn’t import user group.'));

            // Send the import back to the fields template
            return $this->_returnFieldsTemplate($import);
        }

        Campaign::$plugin->imports->queueImport($import);

        // Log it
        Campaign::$plugin->logUserAction('User group "{userGroup}" imported by "{username}".', ['userGroup' => $import->getUserGroup()->name], __METHOD__);

        Craft::$app->getSession()->setNotice(Craft::t('campaign', 'User group successfully queued for importing.'));

        return $this->redirectToPostedUrl($import);
    }

    /**
     * Downloads a file
     *
     * @return Response|null
     * @throws BadRequestHttpException
     * @throws InvalidConfigException
     */
    public function actionDownloadFile()
    {
        $importId = Craft::$app->getRequest()->getRequiredParam('importId');

        $import = Campaign::$plugin->imports->getImportById($importId);

        if ($import == null OR !file_exists($import->filePath)) {
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
     * @throws \Exception
     * @throws \Throwable
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
     *
     * @return Response
     * @throws InvalidConfigException
     * @throws \craft\errors\SiteNotFoundException
     */
    private function _returnFieldsTemplate(ImportModel $import): Response
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

        // Get contact fields
        /** @var FieldLayoutBehavior $fieldLayoutBehavior */
        $fieldLayoutBehavior = Campaign::$plugin->getSettings()->getBehavior('contactFieldLayout');
        $fieldLayout = $fieldLayoutBehavior->getFieldLayout();
        $variables['fields'] = $fieldLayout->getFields();

        // Get columns
        $variables['columns'] = Campaign::$plugin->imports->getColumns($import);

        return $this->renderTemplate('campaign/contacts/import/_fields', $variables);
    }
}