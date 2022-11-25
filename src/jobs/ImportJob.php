<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\jobs;

use Craft;
use craft\helpers\Queue;
use craft\queue\BaseJob;
use craft\queue\jobs\UpdateSearchIndex;
use DateTime;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\events\ImportEvent;
use putyourlightson\campaign\services\ImportsService;

class ImportJob extends BaseJob
{
    /**
     * @var int
     */
    public int $importId;

    /**
     * @inheritdoc
     */
    public function execute($queue): void
    {
        $import = Campaign::$plugin->imports->getImportById($this->importId);

        if ($import === null) {
            return;
        }

        // Fire a before event
        $event = new ImportEvent([
            'import' => $import,
        ]);
        Campaign::$plugin->imports->trigger(ImportsService::EVENT_BEFORE_IMPORT, $event);

        if (!$event->isValid) {
            return;
        }

        // Call for max power
        Campaign::$plugin->maxPowerLieutenant();

        // Get rows
        $rows = Campaign::$plugin->imports->getRows($import);
        $total = count($rows);

        // Loop as long as the there are lines
        foreach ($rows as $i => $row) {
            // Set progress
            $this->setProgress($queue, $i / $total);

            // Import row
            $import = Campaign::$plugin->imports->importRow($import, $row, $i + 1);
        }

        $import->dateImported = new DateTime();

        // Save import
        Campaign::$plugin->imports->saveImport($import);

        // Update the search indexes
        Campaign::$plugin->imports->updateSearchIndexes();

        // Fire an after event
        if (Campaign::$plugin->imports->hasEventHandlers(ImportsService::EVENT_AFTER_IMPORT)) {
            Campaign::$plugin->imports->trigger(ImportsService::EVENT_AFTER_IMPORT, $event);
        }
    }

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): string
    {
        return Craft::t('campaign', 'Importing contacts.');
    }
}
