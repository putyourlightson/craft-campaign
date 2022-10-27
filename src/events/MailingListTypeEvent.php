<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\events;

use craft\events\CancelableEvent;
use putyourlightson\campaign\models\MailingListTypeModel;

class MailingListTypeEvent extends CancelableEvent
{
    /**
     * @var MailingListTypeModel|null
     */
    public ?MailingListTypeModel $mailingListType = null;

    /**
     * @var bool
     */
    public bool $isNew = false;
}
