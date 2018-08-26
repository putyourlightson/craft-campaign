<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\jobs;

use Craft;
use craft\queue\BaseJob;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\events\ImportEvent;
use putyourlightson\campaign\services\ImportsService;

/**
 * ImportJob
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0   
 */
class ImportJob extends BaseJob
{
    // Properties
    // =========================================================================

    /**
     * @var int
     */
    public $importId;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     * @throws \Exception
     * @throws \Throwable
     */
    public function execute($queue)
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
        $total = \count($rows);

        // Loop as long as the there are lines
        foreach ($rows as $i => $row) {
            // Set progress
            $this->setProgress($queue, $i / $total);

            // Import row
            $import = Campaign::$plugin->imports->importRow($import, $row, $i + 1);
        }

        $import->dateImported = new \DateTime();

        // Save import
        Campaign::$plugin->imports->saveImport($import);

        // Fire an after event
        if (Campaign::$plugin->imports->hasEventHandlers(ImportsService::EVENT_AFTER_IMPORT)) {
            Campaign::$plugin->imports->trigger(ImportsService::EVENT_AFTER_IMPORT, new ImportEvent([
                'import' => $import,
            ]));
        }
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): string
    {
        return Craft::t('campaign', 'Importing contacts.');
    }
}