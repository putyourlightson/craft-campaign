<?php

/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

return [
    [
        'id' => '1000',
        'name' => 'Test 1',
        'handle' => 'test1',
        'siteId' => Craft::$app->getSites()->getPrimarySite()->id,
        'subscribeVerificationEmailRequired' => true,
        'subscribeVerificationEmailSubject' => 'Subscribe Verification Email Subject',
        'unsubscribeFormAllowed' => true,
        'unsubscribeVerificationEmailSubject' => 'Unsubscribe Verification Email Subject',
    ],
    [
        'id' => '1001',
        'name' => 'Test 2',
        'handle' => 'test2',
        'siteId' => Craft::$app->getSites()->getPrimarySite()->id,
        'subscribeVerificationEmailRequired' => false,
        'unsubscribeFormAllowed' => false,
    ],
];
