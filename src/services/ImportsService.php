<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\services;

use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\jobs\ImportJob;
use putyourlightson\campaign\models\ImportModel;
use putyourlightson\campaign\records\ImportRecord;

use Craft;
use craft\base\Component;
use craft\base\Field;
use craft\base\FieldInterface;
use craft\behaviors\FieldLayoutBehavior;
use craft\elements\User;
use yii\base\InvalidConfigException;

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
     * @var bool
     */
    private $_handle = false;

    /**
     * @var MailingListElement|null
     */
    private $_mailingList;


    // Public Methods
    // =========================================================================

    /**
     * Returns contact fields
     *
     * @return FieldInterface[]
     * @throws InvalidConfigException
     */
    public function getContactFields(): array
    {
        /** @var FieldLayoutBehavior $fieldLayoutBehavior */
        $fieldLayoutBehavior = Campaign::$plugin->getSettings()->getBehavior('contactFieldLayout');
        $fieldLayout = $fieldLayoutBehavior->getFieldLayout();

        return $fieldLayout->getFields();
    }

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

        $import = new ImportModel();
        $import->setAttributes($importRecord->getAttributes(), false);

        return $import;
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
        if ($import->filePath) {
            $handle = $this->_getHandle($import->filePath);

            if ($handle === false) {
                return $columns;
            }

            $columns = fgetcsv($handle);
        }

        // If user group
        else {
            $columns = [
                'email' => Craft::t('campaign', 'Email'),
                'firstName' => Craft::t('campaign', 'First Name'),
                'lastName' => Craft::t('campaign', 'Last Name'),
            ];

            $fields = Craft::$app->fields->getFieldsByElementType(User::class);
            foreach ($fields as $field) {
                /* @var Field $field */
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
    public function getRows(ImportModel $import, int $offset = 0, int $length = null): array
    {
        $rows = [];

        // If CSV file
        if ($import->filePath) {
            $handle = $this->_getHandle($import->filePath);

            if ($handle === false) {
                return $rows;
            }

            $i = 0;
            $count = 0;

            // Increment offset to skip columns row
            $offset++;

            while ($row = fgetcsv($handle)) {
                if ($length !== null AND $count >= $length) {
                    break;
                }

                if ($i >= $offset) {
                    $rows[] = $row;
                    $count++;
                }

                $i++;
            }
        }

        // If user group
        else {
            $rows = User::find()->groupId($import->userGroupId)->all();
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
        $importRecord->userId = Craft::$app->getUser()->getIdentity()->id;

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
        Craft::$app->getQueue()->push(new ImportJob(['import' => $import]));
    }

    /**
     * Imports a row into a contact
     *
     * @param ImportModel $import
     * @param array|null $row
     * @param int $lineNumber
     *
     * @return ImportModel
     * @throws \Throwable if reasons
     */
    public function importRow(ImportModel $import, $row, int $lineNumber): ImportModel
    {
        // Get mailing list
        $mailingList = Campaign::$plugin->mailingLists->getMailingListById($import->mailingListId);

        if ($mailingList === null) {
            return $import;
        }

        // Get email
        $email = $row[$import->emailFieldIndex];

        // Check if contact exists
        $contact = Campaign::$plugin->contacts->getContactByEmail($email);

        // If contact doesn't exist then create one
        if ($contact === null) {
            $contact = new ContactElement();
            $contact->email = $email;
            $import->added++;
        }
        else {
            $import->updated++;
        }

        // Map fields to values
        if (is_array($import->fieldIndexes)) {
            $values = [];
            foreach ($import->fieldIndexes as $field => $index) {
                if ($index !== '' AND isset($row[$index])) {
                    $values[$field] = $row[$index];
                }
            }

            // Set field values
            $contact->setFieldValues($values);
        }

        // Save it
        if (!Craft::$app->getElements()->saveElement($contact)) {
            $import->failed++;
            $import->failures[$lineNumber] = implode(',', $row);

            return $import;
        }

        // Save import
        Campaign::$plugin->imports->saveImport($import);

        // Add contact interaction
        Campaign::$plugin->mailingLists->addContactInteraction($contact, $mailingList, 'subscribed', 'import', $import->id);

        return $import;
    }

    /**
     * Deletes an import
     *
     * @param int $importId
     *
     * @return bool Whether the action was successful
     * @throws \Exception|\Throwable in case delete failed.
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

    // Private Methods
    // =========================================================================

    private function _getHandle(string $filePath)
    {
        if ($this->_handle !== false) {
            return $this->_handle;
        }

        // Open file for reading
        $this->_handle = fopen($filePath, 'rb');

        return $this->_handle;
    }
}