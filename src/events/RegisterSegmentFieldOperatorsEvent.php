<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\events;

use yii\base\Event;

/**
 * RegisterSegmentFieldOperatorsEvent
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 */
class RegisterSegmentFieldOperatorsEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var array
     */
    public $fieldOperators = [];
}
