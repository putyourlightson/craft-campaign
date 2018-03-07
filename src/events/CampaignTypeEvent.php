<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\events;

use putyourlightson\campaign\models\CampaignTypeModel;

use yii\base\Event;

/**
 * CampaignTypeEvent
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 */
class CampaignTypeEvent extends Event
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
