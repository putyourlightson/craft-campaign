<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\services;

use craft\base\Component;
use craft\base\Element;
use craft\elements\db\ElementQuery;
use craft\fields\data\MultiOptionsFieldData;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\events\ExportEvent;

use putyourlightson\campaign\models\ExportModel;
use putyourlightson\campaign\records\ContactMailingListRecord;

class ExportsService extends Component
{
    /**
     * @event ExportEvent
     */
    public const EVENT_BEFORE_EXPORT = 'beforeExport';

    /**
     * @event ExportEvent
     */
    public const EVENT_AFTER_EXPORT = 'afterExport';

    /**
     * Exports to a CSV file.
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

                            $value = implode(',', $elements);
                        }
                        // https://github.com/putyourlightson/craft-campaign/issues/297
                        elseif ($value instanceof MultiOptionsFieldData) {
                            $value = implode(',', iterator_to_array($value));
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
