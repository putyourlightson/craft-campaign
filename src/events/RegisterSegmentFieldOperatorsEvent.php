<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\events;

use yii\base\Event;

class RegisterSegmentFieldOperatorsEvent extends Event
{
    /**
     * @var array
     */
    public array $fieldOperators = [];
}
