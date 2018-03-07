<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\services;

use putyourlightson\campaign\models\ExportModel;

use craft\base\Component;

/**
 * ExportsService
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 */
class ExportsService extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * Exports to a CSV file
     *
     * @param ExportModel $export
     *
     * @return bool Whether the export was successful
     * @throws \Throwable if reasons
     */
    public function exportFile(ExportModel $export): bool
    {
        // Open file for writing
        $handle = fopen($export->filePath, 'wb');

        // Write field names to file
        fputcsv($handle, $export->fields);

        $contactIds = [];
        $mailingLists = $export->getMailingLists();

        foreach ($mailingLists as $mailingList) {
            // Get subscribed contacts
            $contacts = $mailingList->getSubscribedContacts();

            foreach ($contacts as $contact) {
                // If not already added
                if (!\in_array($contact->id, $contactIds, true)) {
                    // Populate row with contact fields
                    $row = [];
                    foreach ($export->fields as $field) {
                        $row[] = $contact->$field;
                    }

                    // Write contact fields to file
                    fputcsv($handle, $row);

                    $contactIds[] = $contact->id;
                }
            }
        }

        // Close file
        fclose($handle);

        return true;
    }
}