<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

return [
    [
        'name' => 'Campaign Type 1',
        'handle' => 'campaignType1',
        'siteId' => 1,
        'uriFormat' => '',
        'htmlTemplate' => 'html',
        'plaintextTemplate' => 'plaintext',
        'queryStringParameters' => 'source=campaign-plugin&medium=email&campaign={{ campaign.title }}',
    ],
    [
        'name' => 'Campaign Type 2',
        'handle' => 'campaignType2',
        'siteId' => 1,
        'uriFormat' => '',
        'htmlTemplate' => 'html-error',
        'plaintextTemplate' => 'plaintext',
        'queryStringParameters' => '',
    ],
];
