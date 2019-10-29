<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\events;

use yii\base\Event;

/**
 * RegisterSegmentAvailableFieldsEvent
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 */
class RegisterSegmentAvailableFieldsEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var array
     */
    public $availableFields = [];
}
