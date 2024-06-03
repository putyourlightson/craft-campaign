<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\batchmailers;

use craft\mail\transportadapters\TransportAdapterInterface;
use craftcms\postmark\Adapter;

class MailgunBatchMailer implements BatchMailerInterface
{
    public const MAX_BATCH_SIZE = 500;

    /**
     * @inheritdoc
     *
     * @param Adapter $adapter
     */
    public function sendBatchEmails(TransportAdapterInterface $adapter, array $emails): bool
    {
        return true;
    }
}
