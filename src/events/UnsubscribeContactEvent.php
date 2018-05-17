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
 * UnsubscribeContactEvent
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 */
class UnsubscribeContactEvent extends SubscribeContactEvent
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
}
