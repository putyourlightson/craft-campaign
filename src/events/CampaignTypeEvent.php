<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\events;

use craft\events\CancelableEvent;
use putyourlightson\campaign\models\CampaignTypeModel;

class CampaignTypeEvent extends CancelableEvent
{
    /**
     * @var CampaignTypeModel|null
     */
    public ?CampaignTypeModel $campaignType = null;

    /**
     * @var bool
     */
    public bool $isNew = false;
}
