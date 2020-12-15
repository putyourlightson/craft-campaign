<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

use putyourlightson\campaign\helpers\StringHelper;

return [
    [
        'pid' => StringHelper::uniqueId('p'),
        'email' => 'pending1@contacts.com',
        'mailingListId' => $this->mailingListId,
        'fieldData' => [],
    ],
];
