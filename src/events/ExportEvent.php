<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\events;

use craft\events\CancelableEvent;
use putyourlightson\campaign\models\ExportModel;

/**
 * ExportEvent
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.2.0
 */
class ExportEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    /**
     * @var ExportModel|null
     */
    public $export;
}
