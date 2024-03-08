<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\services;

use craft\base\Component;
use craft\base\Element;
use craft\elements\db\ElementQuery;
use craft\fields\data\MultiOptionsFieldData;
use craft\helpers\App;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\MailingListElement;
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

        App::maxPowerCaptain();

        // Open file for writing
        $handle = fopen($export->filePath, 'wb');

        // Get selected field handles
        $fieldHandles = array_keys(array_filter($export->fields, fn($value) => $value));

        // Output column titles
        fputcsv($handle, array_merge([
            'mailingList',
            'subscriptionStatus',
            'subscribedDate',
        ], $fieldHandles));

        $mailingLists = $export->getMailingLists();

        foreach ($mailingLists as $mailingList) {
            // Get all contacts in mailing list
            $contacts = ContactElement::find()
                ->mailingListId($mailingList->id)
                ->all();

            foreach ($contacts as $contact) {
                $subscription = $this->_getSubscription($contact, $mailingList);

                $row = [];
                $row[] = $mailingList->title;
                $row[] = $subscription->subscriptionStatus;
                $row[] = $subscription->subscribed;

                foreach ($fieldHandles as $fieldHandle) {
                    $value = $contact->{$fieldHandle};

                    if ($value instanceof ElementQuery) {
                        $elements = $value->all();

                        // Use the string representation of each element
                        /** @var Element $element */
                        foreach ($elements as &$element) {
                            $element = $element->__toString();
                        }

                        $value = implode(',', $elements);
                    } // https://github.com/putyourlightson/craft-campaign/issues/297
                    elseif ($value instanceof MultiOptionsFieldData) {
                        $value = implode(',', iterator_to_array($value));
                    }

                    $row[] = $value;
                }

                // Write contact fields to file
                fputcsv($handle, $row);
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

    private function _getSubscription(ContactElement $contact, MailingListElement $mailingList): ?ContactMailingListRecord
    {
        /** @var ContactMailingListRecord|null */
        return ContactMailingListRecord::find()
            ->select([
                'subscriptionStatus',
                'subscribed',
            ])
            ->where([
                'contactId' => $contact->id,
                'mailingListId' => $mailingList->id,
            ])
            ->one();
    }
}
