<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

/**
 * Campaign config.php
 *
 * This file exists only as a template for the Campaign settings.
 * It does nothing on its own.
 *
 * Don't edit this file, instead copy it to 'craft/config' as 'campaign.php'
 * and make your changes there to override default settings.
 *
 * Once copied to 'craft/config', this file will be multi-environment aware as
 * well, so you can have different settings groups for each environment, just as
 * you do for 'general.php'
 */

return [
    // Setting to true will save email messages into local files (in storage/runtime/debug/mail) rather than actually sending them
    //'testMode' => false,

    // An API key to use for triggering tasks and notifications (min. 16 characters)
    //'apiKey' => 'aBcDeFgHiJkLmNoP',

    // The default name to send emails from
    //'defaultFromName' => 'Zorro',

    // The default email address to send emails from
    //'defaultFromEmail' => 'legend@zorro.com',

    // A label to use for the email field
    //'emailFieldLabel' => 'Email',

    // The amount of time to wait before purging pending contacts in seconds or as an interval (0 for disabled)
    //'purgePendingContactsDuration' => 0,

    // The threshold for memory usage per sendout batch as a fraction
    //'memoryThreshold' => 0.8,

    // The threshold for execution time per sendout batch as a fraction
    //'timeThreshold' => 0.8,

    // The memory usage limit per sendout batch in bytes or a shorthand byte value (set to -1 for unlimited)
    //'memoryLimit' => '1024M',

    // The execution time limit per sendout batch in seconds (set to 0 for unlimited)
    //'timeLimit' => 300,

    // The max size of sendout batches
    //'maxBatchSize' => 1000,

    // The max number of sendout retry attempts
    //'maxRetryAttempts' => 10,

    // The amount of time in seconds to delay jobs between sendout batches
    //'batchJobDelay' => 10,
];
