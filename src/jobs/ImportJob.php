<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\jobs;

use putyourlightson\campaign\Campaign;

use Craft;
use craft\queue\BaseJob;
use putyourlightson\campaign\models\ImportModel;

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
     * @var ImportModel
     */
    public $import;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     * @throws \Exception
     */
    public function execute($queue)
    {
        $import = $this->import;

        // Get rows
        $rows = Campaign::$plugin->imports->getRows($import);
        $totalRows = \count($rows);

        // Loop as long as the there are lines
        foreach ($rows as $i => $row) {
            // Set progress
            $progress = $i / $totalRows;
            $this->setProgress($queue, $progress);

            // Import row
            $import = Campaign::$plugin->imports->importRow($import, $row, $i + 1);
        }

        $import->dateImported = new \DateTime();

        // Save import
        Campaign::$plugin->imports->saveImport($import);
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): string
    {
        return Craft::t('campaign', 'Importing CSV file.');
    }
}