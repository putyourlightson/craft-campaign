<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\services;

use craft\base\Element;
use craft\elements\db\ElementQuery;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\events\ExportEvent;
use putyourlightson\campaign\models\ExportModel;

use craft\base\Component;
use putyourlightson\campaign\records\ContactMailingListRecord;
use Throwable;

/**
 * ExportsService
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 */
class ExportsService extends Component
{
    // Constants
    // =========================================================================

    /**
     * @event ExportEvent
     */
    const EVENT_BEFORE_EXPORT = 'beforeExport';

    /**
     * @event ExportEvent
     */
    const EVENT_AFTER_EXPORT = 'afterExport';

    // Public Methods
    // =========================================================================

    /**
     * Exports to a CSV file
     *
     * @param ExportModel $export
     *
     * @return bool Whether the export was successful
     * @throws Throwable if reasons
     */
    public function exportFile(ExportModel $export): bool
    {
        // Fire a before event
        $event = new ExportEvent([
            'export' => $export,
        ]);
        $this->trigger(self::EVENT_BEFORE_EXPORT, $event);

        if (!$event->isValid) {
            return false;
        }

        // Call for max power
        Campaign::$plugin->maxPowerLieutenant();

        // Open file for writing
        $handle = fopen($export->filePath, 'wb');

        // Write field names to file
        $fieldNames = $export->fields;

        if ($export->subscribedDate) {
            $fieldNames[] = 'subscribedDate';
        }

        fputcsv($handle, $fieldNames);

        $contactIds = [];
        $mailingLists = $export->getMailingLists();

        foreach ($mailingLists as $mailingList) {
            // Get subscribed contacts
            $contacts = $mailingList->getSubscribedContacts();

            foreach ($contacts as $contact) {
                // If not already added
                if (!in_array($contact->id, $contactIds)) {
                    // Populate row with contact fields
                    $row = [];
                    foreach ($export->fields as $field) {
                        $value = $contact->{$field};

                        if ($value instanceof ElementQuery) {
                            $elements = $value->all();

                            // Use the string representation of each element
                            /** @var Element $element */
                            foreach ($elements as &$element) {
                                $element = $element->__toString();
                            }

                            // Unset variable reference to avoid possible side-effects
                            unset($element);

                            $value = implode(',', $elements);
                        }

                        $row[] = $value;
                    }

                    // If subscribed date should be added
                    if ($export->subscribedDate) {
                        $subscribedDate = ContactMailingListRecord::find()
                            ->select('subscribed')
                            ->where([
                                'contactId' => $contact->id,
                                'mailingListId' => $mailingList->id,
                            ])
                            ->scalar();

                        $row[] = $subscribedDate;
                    }

                    // Write contact fields to file
                    fputcsv($handle, $row);

                    $contactIds[] = $contact->id;
                }
            }
        }

        // Close file
        fclose($handle);

        // Fire an after event
        if ($this->hasEventHandlers(self::EVENT_AFTER_EXPORT)) {
            $this->trigger(self::EVENT_AFTER_EXPORT, new ExportEvent([
                'export' => $export,
            ]));
        }

        return true;
    }
}
