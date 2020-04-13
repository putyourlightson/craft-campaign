<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\events;

use craft\events\CancelableEvent;
use putyourlightson\campaign\models\MailingListTypeModel;

/**
 * MailingListTypeEvent
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 */
class MailingListTypeEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    /**
     * @var MailingListTypeModel|null
     */
    public $mailingListType;

    /**
     * @var bool
     */
    public $isNew = false;
}
