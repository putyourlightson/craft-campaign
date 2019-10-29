<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

use putyourlightson\campaign\helpers\StringHelper;

return [
    [
        'pid' => StringHelper::uniqueId('p'),
        'mailingListId' => $this->mailingListId ?? null,
        'email' => 'pending1@contacts.com',
        'fieldData' => [],
    ],
];
