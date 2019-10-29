<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\events;

use craft\events\CancelableEvent;
use putyourlightson\campaign\models\ImportModel;

/**
 * ImportEvent
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.2.0
 */
class ImportEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    /**
     * @var ImportModel|null
     */
    public $import;
}
