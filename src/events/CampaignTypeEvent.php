<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\events;

use craft\events\CancelableEvent;
use putyourlightson\campaign\models\CampaignTypeModel;

/**
 * CampaignTypeEvent
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 */
class CampaignTypeEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    /**
     * @var CampaignTypeModel|null
     */
    public $campaignType;

    /**
     * @var bool
     */
    public $isNew = false;
}
