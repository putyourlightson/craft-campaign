<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\batchmailers;

use putyourlightson\campaign\records\BatchEmailRecord;

interface BatchMailerInterface
{
    /**
     * Sends batch emails.
     *
     * @param BatchEmailRecord[] $emails
     */
    public function sendBatchEmails(array $emails): void;
}
