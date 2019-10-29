<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

return [
    [
        'name' => 'Mailing List Type 1',
        'handle' => 'mailingListType1',
        'siteId' => 1,
        'subscribeVerificationRequired' => false,
        'unsubscribeFormAllowed' => false,
    ],
    [
        'name' => 'Mailing List Type 2',
        'handle' => 'mailingListType2',
        'siteId' => 1,
        'subscribeVerificationRequired' => true,
        'subscribeVerificationEmailSubject' => 'Subscribe Verification Email Subject',
        'unsubscribeFormAllowed' => true,
        'unsubscribeVerificationEmailSubject' => 'Unsubscribe Verification Email Subject',
    ],
];
