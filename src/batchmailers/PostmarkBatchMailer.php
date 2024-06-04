<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\batchmailers;

use GuzzleHttp\Exception\ConnectException;

/**
 * https://postmarkapp.com/developer/user-guide/send-email-with-api/batch-emails
 */
class PostmarkBatchMailer extends BaseBatchMailer
{
    public const MAX_BATCH_SIZE = 500;

    public ?string $token = null;

    public ?string $messageStream = null;

    /**
     * @inheritdoc
     */
    public function sendBatchEmails(array $emails): bool
    {
        $messages = [];

        foreach ($emails as $email) {
            $messages[] = [
                'From' => $email->fromEmail,
                'To' => $email->to,
                'Subject' => $email->subject,
                'TextBody' => $email->plaintextBody,
                'HtmlBody' => $email->htmlBody,
                'MessageStream' => $this->messageStream,
            ];
        }

        $client = $this->getClient();

        while (count($messages) > 0) {
            $batchMessages = array_splice($messages, 0, self::MAX_BATCH_SIZE);

            try {
                $client->post('https://api.postmarkapp.com/email/batch', [
                    'headers' => [
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                        'X-Postmark-Server-Token' => $this->token,
                    ],
                    'form_params' => $batchMessages,
                ]);
            } catch (ConnectException) {
                return false;
            }
        }

        return true;
    }
}
