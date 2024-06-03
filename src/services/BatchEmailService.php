<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\services;

use craft\base\Component;
use craft\helpers\ArrayHelper;
use craft\helpers\Component as ComponentHelper;
use craft\mail\Message;
use putyourlightson\campaign\batchmailers\BatchMailerInterface;
use putyourlightson\campaign\batchmailers\PostmarkBatchMailer;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\SendoutElement;
use putyourlightson\campaign\records\BatchEmailRecord;

class BatchEmailService extends Component
{
    const BATCH_MAILERS = [
        'craftcms\postmark\Adapter' => PostmarkBatchMailer::class,
    ];

    /**
     * Adds a message to the email batch.
     */
    public function addMessage(SendoutElement $sendout, Message $message): bool
    {
        $record = new BatchEmailRecord();
        $record->sid = $sendout->sid;

        $from = $message->getFrom();
        $record->fromName = ArrayHelper::firstValue($from);
        $record->fromEmail = array_key_first($from);
        $record->to = $message->getTo();
        $record->subject = $message->getSubject();
        $record->htmlBody = $message->getHtmlBody();
        $record->plaintextBody = $message->getTextBody();

        return $record->save();
    }

    /**
     * Sends batched emails for the provided sendout.
     */
    public function sendBatchEmails(SendoutElement $sendout): bool
    {
        $settings = Campaign::$plugin->getSettings();
        if (!$settings->isBatchEmailSendingSupported($settings->transportType)) {
            return false;
        }

        $emails = BatchEmailRecord::find()
            ->where(['sid' => $sendout->sid])
            ->all();

        $batchMailer = $this->createBatchMailer();

        return $batchMailer->sendBatchEmails($emails);
    }

    private function createBatchMailer(): BatchMailerInterface
    {
        $transportType = Campaign::$plugin->settings->transportType;
        $batchMailerType = self::BATCH_MAILERS[$transportType];

        return ComponentHelper::createComponent([
            'type' => $batchMailerType,
            'settings' => Campaign::$plugin->settings->transportSettings,
        ], BatchMailerInterface::class);
    }
}
