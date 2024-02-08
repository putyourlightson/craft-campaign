<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\services;

use Craft;
use craft\base\Component;
use craft\elements\User;
use craft\fields\BaseOptionsField;
use craft\fields\BaseRelationField;
use craft\helpers\Json;
use craft\helpers\Queue;
use craft\queue\jobs\UpdateSearchIndex;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\jobs\ImportJob;
use putyourlightson\campaign\models\ImportModel;
use putyourlightson\campaign\records\ImportRecord;

/**
 * @property-read ImportModel[] $allImports
 */
class ImportsService extends Component
{
    /**
     * @event ImportEvent
     */
    public const EVENT_BEFORE_IMPORT = 'beforeImport';

    /**
     * @event ImportEvent
     */
    public const EVENT_AFTER_IMPORT = 'afterImport';

    /**
     * @const string[]
     */
    public const JSON_DECODE_FIELDS = [
        BaseOptionsField::class,
        BaseRelationField::class,
    ];

    /**
     * @var array
     */
    private array $_mailingLists = [];

    /**
     * @var array
     */
    private array $_importedContactIds = [];

    /**
     * Returns all imports.
     *
     * @return ImportModel[]
     */
    public function getAllImports(): array
    {
        $imports = [];

        /** @var ImportRecord[] $importRecords */
        $importRecords = ImportRecord::find()->all();

        foreach ($importRecords as $importRecord) {
            $import = new ImportModel();
            $import->setAttributes($importRecord->getAttributes(), false);
            $imports[] = $import;
        }

        return $imports;
    }

    /**
     * Returns an import by ID.
     */
    public function getImportById(int $importId): ?ImportModel
    {
        if (!$importId) {
            return null;
        }

        $importRecord = ImportRecord::findOne($importId);

        if ($importRecord === null) {
            return null;
        }

        $import = new ImportModel();
        $import->setAttributes($importRecord->getAttributes(), false);

        return $import;
    }

    /**
     * Returns an import's resource handle.
     *
     * @return resource|null
     */
    public function getHandle(ImportModel $import)
    {
        $handle = null;

        // Set run-time configuration to `1` to ensure line endings are recognised when delimited with "\r"
        ini_set('auto_detect_line_endings', '1');

        if ($import->filePath) {
            // Open file for reading
            $handle = @fopen($import->filePath, 'rb');

            // Convert handle from false boolean to null
            if ($handle === false) {
                $handle = null;
            }
        } elseif ($import->assetId !== null) {
            $asset = Craft::$app->getAssets()->getAssetById($import->assetId);

            if ($asset === null) {
                return null;
            }

            $handle = $asset->getStream();
        }

        return $handle;
    }

    /**
     * Returns columns.
     */
    public function getColumns(ImportModel $import): array
    {
        $columns = [];

        if ($import->fileName) {
            $handle = $this->getHandle($import);

            if ($handle === null) {
                return $columns;
            }

            $columns = fgetcsv($handle);
        } else {
            $columns = [
                'email' => Craft::t('campaign', 'Email'),
                'fullName' => Craft::t('campaign', 'Full Name'),
                'firstName' => Craft::t('campaign', 'First Name'),
                'lastName' => Craft::t('campaign', 'Last Name'),
            ];

            $fieldLayout = Craft::$app->getFields()->getLayoutByType(User::class);
            $fields = $fieldLayout->getCustomFields();

            foreach ($fields as $field) {
                $columns[$field->handle] = $field->name;
            }
        }

        return $columns;
    }

    /**
     * Returns rows.
     */
    public function getRows(ImportModel $import, int $offset = null, int $length = null): array
    {
        $offset = $offset ?? 0;
        $rows = [];

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
        } else {
            // Get rows as arrays
            $rows = User::find()
                ->groupId($import->userGroupId)
                ->asArray()
                ->all();
        }

        return $rows;
    }

    /**
     * Saves an import.
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
        } else {
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
     * Queues an import.
     */
    public function queueImport(ImportModel $import): void
    {
        // Add import job to queue
        Craft::$app->getQueue()->push(new ImportJob(['importId' => $import->id]));
    }

    /**
     * Imports a row into a contact.
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
        $email = trim($row[$import->emailFieldIndex]);

        // Check if contact exists
        $contact = Campaign::$plugin->contacts->getContactByEmail($email);
        $newContact = false;

        // If contact doesn't exist then create one
        if ($contact === null) {
            $contact = new ContactElement();
            $contact->email = $email;

            $newContact = true;
        }

        // Skip if blocked
        if ($contact->blocked !== null) {
            return $import;
        }

        // Map fields to values
        if (is_array($import->fieldIndexes)) {
            $values = [];

            foreach ($import->fieldIndexes as $fieldHandle => $index) {
                if ($index !== '' && isset($row[$index])) {
                    $values[$fieldHandle] = $row[$index];

                    $field = $contact->getFieldLayout()->getFieldByHandle($fieldHandle);

                    // JSON decode if the field is an instance of specific base fields.
                    foreach (self::JSON_DECODE_FIELDS as $class) {
                        if ($field instanceof $class) {
                            $values[$fieldHandle] = Json::decodeIfJson($row[$index]);
                        }
                    }
                }
            }

            // Set field values
            $contact->setFieldValues($values);
        }

        // Save contact without updating the search index
        $success = Craft::$app->getElements()->saveElement($contact, true, true, false);

        if (!$success) {
            $import->failures++;

            Campaign::$plugin->log('Line ' . $lineNumber . ': ' . implode('. ', $contact->getErrorSummary(true)));

            Campaign::$plugin->imports->saveImport($import);

            return $import;
        }

        if ($newContact) {
            $import->added++;
        } else {
            $import->updated++;
        }

        Campaign::$plugin->imports->saveImport($import);

        if ($import->unsubscribe) {
            Campaign::$plugin->mailingLists->addContactInteraction($contact, $mailingList, 'unsubscribed', 'import', $import->id);
        } elseif ($contact->getMailingListSubscriptionStatus($import->mailingListId) == '' || $import->forceSubscribe) {
            Campaign::$plugin->mailingLists->addContactInteraction($contact, $mailingList, 'subscribed', 'import', $import->id);
        }

        $this->_importedContactIds[] = $contact->id;

        return $import;
    }

    /**
     * Deletes an import.
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

    /**
     * Updates the search indexes of imported contacts.
     */
    public function updateSearchIndexes(): void
    {
        $customFields = Campaign::$plugin->settings->getContactFields();
        $fieldHandles = array_map(fn($field) => $field->handle, $customFields);
        $fieldHandles[] = 'email';

        $job = new UpdateSearchIndex([
            'elementType' => ContactElement::class,
            'elementId' => $this->_importedContactIds,
            'siteId' => '*',
            'fieldHandles' => $fieldHandles,
        ]);

        Queue::push(
            $job,
            Campaign::$plugin->settings->importJobPriority,
            null,
            Campaign::$plugin->settings->importJobTtr,
            Campaign::$plugin->queue,
        );
    }
}
