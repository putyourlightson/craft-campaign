<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\events;

use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\SendoutElement;
use putyourlightson\campaign\records\LinkRecord;
use yii\base\Event;

class ContactInteractionEvent extends Event
{
    /**
     * @var ContactElement
     */
    public ContactElement $contact;

    /**
     * @var SendoutElement
     */
    public SendoutElement $sendout;

    /**
     * @var string
     */
    public string $interaction;

    /**
     * @var LinkRecord|null
     */
    public ?LinkRecord $link;
}
