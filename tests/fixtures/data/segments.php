<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

return [
    [
        'title' => 'Segment 1',
        'segmentType' => 'regular',
        'conditions' => [
            [
                ['like', 'email', 'contact@'],
            ],
        ],
    ],
    [
        'title' => 'Segment 2',
        'segmentType' => 'template',
        'template' => '{{ "contact@" in contact.email ? 1 : 0 }}',
    ],
];
