<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\events;

use putyourlightson\campaign\elements\ContactElement;
use yii\base\Event;

/**
 * UpdateContactEvent
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.5.0
 */
class UpdateContactEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var ContactElement
     */
    public $contact;
}
