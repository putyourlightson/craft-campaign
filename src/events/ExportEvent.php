<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\events;

use craft\events\CancelableEvent;
use putyourlightson\campaign\models\ExportModel;

/**
 * @since 1.2.0
 */
class ExportEvent extends CancelableEvent
{
    /**
     * @var ExportModel|null
     */
    public ?ExportModel $export;
}
