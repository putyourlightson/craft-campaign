<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\events;

use craft\events\CancelableEvent;
use putyourlightson\campaign\models\ImportModel;

/**
 * @since 1.2.0
 */
class ImportEvent extends CancelableEvent
{
    /**
     * @var ImportModel|null
     */
    public ?ImportModel $import = null;
}
