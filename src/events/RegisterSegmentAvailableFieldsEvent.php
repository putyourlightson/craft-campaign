<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\events;

use yii\base\Event;

class RegisterSegmentAvailableFieldsEvent extends Event
{
    /**
     * @var array
     */
    public array $availableFields = [];
}
