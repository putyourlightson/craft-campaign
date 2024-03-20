<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\jobs;

use Craft;
use craft\queue\BaseBatchedJob;
use DateTime;
use putyourlightson\campaign\batchers\RowBatcher;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\events\ImportEvent;
use putyourlightson\campaign\models\ImportModel;
use putyourlightson\campaign\services\ImportsService;

class ImportJob extends BaseBatchedJob
{
    /**
     * @var int
     */
    public int $importId;

    /**
     * @var ImportModel|null
     */
    private ?ImportModel $import = null;

    /**
     * @inheritdoc
     */
    public function execute($queue): void
    {
        $import = $this->getImport();
        if ($import === null) {
            return;
        }

        $event = new ImportEvent([
            'import' => $import,
        ]);
        Campaign::$plugin->imports->trigger(ImportsService::EVENT_BEFORE_IMPORT, $event);

        if (!$event->isValid) {
            return;
        }

        parent::execute($queue);

        // TODO: move what’s below this into the `BaseBatchedJob::after` method in Campaign 3.

        if ($this->itemOffset < $this->totalItems()) {
            return;
        }

        $import->dateImported = new DateTime();

        Campaign::$plugin->imports->saveImport($import);
        Campaign::$plugin->imports->updateSearchIndexes();

        if (Campaign::$plugin->imports->hasEventHandlers(ImportsService::EVENT_AFTER_IMPORT)) {
            Campaign::$plugin->imports->trigger(ImportsService::EVENT_AFTER_IMPORT, $event);
        }
    }

    /**
     * @inheritdoc
     */
    protected function loadData(): RowBatcher
    {
        $import = $this->getImport();
        if ($import === null) {
            return new RowBatcher([]);
        }

        $rows = Campaign::$plugin->imports->getRows($import);

        return new RowBatcher($rows);
    }

    /**
     * @inheritdoc
     */
    protected function processItem(mixed $item): void
    {
        Campaign::$plugin->imports->importRow($this->getImport(), $item);
    }

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): string
    {
        return Craft::t('campaign', 'Importing contacts');
    }

    private function getImport(): ?ImportModel
    {
        if ($this->import === null) {
            $this->import = Campaign::$plugin->imports->getImportById($this->importId);
        }

        return $this->import;
    }
}
