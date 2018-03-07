<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\events;

use putyourlightson\campaign\models\MailingListTypeModel;

use yii\base\Event;

/**
 * MailingListTypeEvent
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 */
class MailingListTypeEvent extends Event
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
