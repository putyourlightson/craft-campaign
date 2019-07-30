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
        'uriFormat' => 'uri-format',
        'htmlTemplate' => 'html',
        'plaintextTemplate' => 'plaintext',
        'queryStringParameters' => 'source=campaign-plugin&medium=email&campaign={{ campaign.title }}',
    ],
    [
        'id' => '1001',
        'name' => 'Test 2',
        'handle' => 'test2',
        'uriFormat' => 'uri-format',
        'htmlTemplate' => 'html',
        'plaintextTemplate' => 'plaintext',
        'queryStringParameters' => 'source=campaign-plugin&medium=email&campaign={{ campaign.title }}',
    ],
];
