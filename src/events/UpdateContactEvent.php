<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\events;

use putyourlightson\campaign\elements\ContactElement;
use yii\base\Event;

/**
 * @since 1.5.0
 */
class UpdateContactEvent extends Event
{
    /**
     * @var ContactElement
     */
    public ContactElement $contact;
}
