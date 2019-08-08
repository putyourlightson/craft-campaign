<?php

/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

use putyourlightson\campaign\elements\SendoutElement;

return [
    [
        'title' => 'Sendout 1',
        'sendStatus' => SendoutElement::STATUS_SENDING,
    ],
    [
        'title' => 'Sendout 2',
        'sendStatus' => SendoutElement::STATUS_FAILED,
    ],

];
