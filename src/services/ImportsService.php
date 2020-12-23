<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\services;

use Exception;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\jobs\ImportJob;
use putyourlightson\campaign\models\ImportModel;
use putyourlightson\campaign\records\ImportRecord;

use Craft;
use craft\base\Component;
use craft\base\Field;
use craft\base\FieldInterface;
use craft\elements\User;
use Throwable;

/**
 * ImportsService
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 *
 * @property FieldInterface[] $contactFields
 * @property ImportModel[] $allImports
 */
class ImportsService extends Component
{
    // Constants
    // =========================================================================

    /**
     * @event ImportEvent
     */
    const EVENT_BEFORE_IMPORT = 'beforeImport';

    /**
     * @event ImportEvent
     */
    const EVENT_AFTER_IMPORT = 'afterImport';

    // Properties
    // =========================================================================

    /**
     * @var array
     */
    private $_mailingLists = [];

    // Public Methods
    // =========================================================================

    /**
     * Returns all imports
     *
     * @return ImportModel[]
     */
    public function getAllImports(): array
    {
        $importRecords = ImportRecord::find()->all();

        return ImportModel::populateModels($importRecords, false);
    }

    /**
     * Returns import by ID
     *
     * @param int $importId
     *
     * @return ImportModel|null
     */
    public function getImportById(int $importId)
    {
        if (!$importId) {
            return null;
        }

        $importRecord = ImportRecord::findOne($importId);

        if ($importRecord === null) {
            return null;
        }

        /** @var ImportModel $import */
        $import = ImportModel::populateModel($importRecord, false);

        return $import;
    }

    /**
     * Returns an import's resource handle.
     *
     * @param ImportModel $import
     *
     * @return resource|null
     */
    public function getHandle(ImportModel $import)
    {
        $handle = null;

        // Set run-time configuration to true to ensure line endings are recognised when delimited with "\r"
        ini_set('auto_detect_line_endings', true);

        if ($import->filePath) {
            // Open file for reading
            $handle = @fopen($import->filePath, 'rb');

            // Convert handle from false boolean to null
            if ($handle === false) {
                $handle = null;
            }
        }
        elseif ($import->assetId !== null) {
            $asset = Craft::$app->getAssets()->getAssetById($import->assetId);

            if ($asset === null) {
                return null;
            }

            $handle = $asset->getStream();
        }

        return $handle;
    }

    /**
     * Returns columns
     *
     * @param ImportModel $import
     *
     * @return array
     */
    public function getColumns(ImportModel $import): array
    {
        $columns = [];

        // If CSV file
        if ($import->fileName) {
            $handle = $this->getHandle($import);

            if ($handle === null) {
                return $columns;
            }

            $columns = fgetcsv($handle);
        }
        // Else user group
        else {
            $columns = [
                'email' => Craft::t('campaign', 'Email'),
                'firstName' => Craft::t('campaign', 'First Name'),
                'lastName' => Craft::t('campaign', 'Last Name'),
            ];

            /** @var Field[] $fields */
            $fields = Craft::$app->fields->getFieldsByElementType(User::class);

            foreach ($fields as $field) {
                $columns[$field->handle] = $field->name;
            }
        }

        return $columns;
    }

    /**
     * Returns rows
     *
     * @param ImportModel $import
     * @param int|null $offset
     * @param int|null $length
     *
     * @return array
     */
    public function getRows(ImportModel $import, int $offset = null, int $length = null): array
    {
        $offset = $offset ?? 0;

        $rows = [];

        // If CSV file
        if ($import->fileName) {
            $handle = $this->getHandle($import);

            if ($handle === null) {
                return [];
            }

            $i = 0;
            $count = 0;

            // Increment offset to skip columns row
            $offset++;

            while ($row = fgetcsv($handle)) {
                if ($length !== null && $count >= $length) {
                    break;
                }

                if ($i >= $offset) {
                    $rows[] = $row;
                    $count++;
                }

                $i++;
            }

            fclose($handle);
        }
        // Else user group
        else {
            // Get rows as arrays
            $rows = User::find()
                ->groupId($import->userGroupId)
                ->asArray()
                ->all();
        }

        return $rows;
    }

    /**
     * Saves an import
     *
     * @param ImportModel $import
     *
     * @return bool Whether the import was saved successfully
     */
    public function saveImport(ImportModel $import): bool
    {
        if ($import->validate() === false) {
            return false;
        }

        if ($import->id) {
            $importRecord = ImportRecord::findOne($import->id);

            if ($importRecord === null) {
                return false;
            }
        }
        else {
            $importRecord = new ImportRecord();
        }

        $importRecord->setAttributes($import->getAttributes(), false);

        // Get user ID
        $importRecord->userId = Craft::$app->getUser()->getId();

        // Save import
        if ($importRecord->save(false) === false) {
            return false;
        }

        // Update import model ID
        $import->id = $importRecord->id;

        return true;
    }

    /**
     * Queues an import
     *
     * @param ImportModel $import
     */
    public function queueImport(ImportModel $import)
    {
        // Add import job to queue
        Craft::$app->getQueue()->push(new ImportJob(['importId' => $import->id]));
    }

    /**
     * Imports a row into a contact
     *
     * @param ImportModel $import
     * @param array $row
     * @param int $lineNumber
     *
     * @return ImportModel
     * @throws Throwable if reasons
     */
    public function importRow(ImportModel $import, array $row, int $lineNumber): ImportModel
    {
        // Get mailing list or memoize it
        if (empty($this->_mailingLists[$import->mailingListId])) {
            $this->_mailingLists[$import->mailingListId] = Campaign::$plugin->mailingLists->getMailingListById($import->mailingListId);
        }

        $mailingList = $this->_mailingLists[$import->mailingListId];

        if ($mailingList === null) {
            return $import;
        }

        // Get email
        $email = $row[$import->emailFieldIndex];

        // Check if contact exists
        $contact = Campaign::$plugin->contacts->getContactByEmail($email);
        $newContact = false;

        // If contact doesn't exist then create one
        if ($contact === null) {
            $contact = new ContactElement();
            $contact->email = $email;

            $newContact = true;
        }

        // Map fields to values
        if (is_array($import->fieldIndexes)) {
            $values = [];

            foreach ($import->fieldIndexes as $field => $index) {
                if ($index !== '' && isset($row[$index])) {
                    $values[$field] = $row[$index];
                }
            }

            // Set field values
            $contact->setFieldValues($values);
        }

        // Save contact
        if (!Craft::$app->getElements()->saveElement($contact)) {
            Campaign::$plugin->log('Line '.$lineNumber.': '.implode('. ', $contact->getErrorSummary(true)));

            $import->fails++;
            Campaign::$plugin->imports->saveImport($import);

            return $import;
        }

        if ($newContact) {
            $import->added++;
        }
        else {
            $import->updated++;
        }

        Campaign::$plugin->imports->saveImport($import);

        // Subscribe contact only if mailing list subscription status is empty or forcing is enabled
        if ($contact->getMailingListSubscriptionStatus($import->mailingListId) == '' || $import->forceSubscribe) {
            Campaign::$plugin->mailingLists->addContactInteraction($contact, $mailingList, 'subscribed', 'import', $import->id);
        }

        return $import;
    }

    /**
     * Deletes an import
     *
     * @param int $importId
     *
     * @return bool Whether the action was successful
     * @throws Exception|Throwable in case delete failed.
     */
    public function deleteImportById(int $importId): bool
    {
        $import = $this->getImportById($importId);

        if ($import === null) {
            return false;
        }

        // Delete the import
        $importRecord = ImportRecord::findOne($importId);

        if ($importRecord !== null) {
            $importRecord->delete();
        }

        return true;
    }
}
