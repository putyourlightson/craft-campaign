<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

$contacts = [
    [
        'email' => 'contact@contacts.com',
        'cid' => 'cid',
        'uid' => 'uid',
    ],
    [
        'email' => 'contact@contacts-deleted.com',
        'dateDeleted' => new DateTime(),
        'cid' => 'cid',
        'uid' => 'uid',
    ],
    [
        'email' => 'contact@contacts-complained.com',
        'complained' => new DateTime(),
        'cid' => 'cid',
        'uid' => 'uid',
    ],
    [
        'email' => 'contact@contacts-bounced.com',
        'bounced' => new DateTime(),
        'cid' => 'cid',
        'uid' => 'uid',
    ],
];

$more = 0;

for ($i = 0; $i < $more; $i++) {
    $contacts[] = [
        'email' => 'contact' . $i . '@contacts.com',
        'cid' => 'cid' . $i,
        'uid' => 'uid' . $i,
    ];
}

return $contacts;
