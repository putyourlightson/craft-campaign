<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\base;

use putyourlightson\campaign\records\BatchEmailRecord;

interface BatchMailerInterface
{
    /**
     * Sends batch emails.
     *
     * @param BatchEmailRecord[] $emails
     */
    public function sendBatchEmails(array $emails): bool;
}
