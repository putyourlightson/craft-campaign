<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\events;

use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\MailingListElement;
use yii\base\Event;

class SubscribeContactEvent extends Event
{
    /**
     * @var ContactElement
     */
    public ContactElement $contact;

    /**
     * @var MailingListElement
     */
    public MailingListElement $mailingList;

    /**
     * @var string
     */
    public string $sourceType;

    /**
     * @var string
     */
    public string $source;
}
