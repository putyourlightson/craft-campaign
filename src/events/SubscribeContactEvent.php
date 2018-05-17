<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\events;

use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\MailingListElement;
use yii\base\Event;

/**
 * SubscribeContactEvent
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 */
class SubscribeContactEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var ContactElement
     */
    public $contact;

    /**
     * @var MailingListElement
     */
    public $mailingList;

    /**
     * @var string
     */
    public $sourceType;

    /**
     * @var string
     */
    public $source;
}
