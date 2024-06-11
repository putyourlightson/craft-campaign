<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\batchmailers;

use Exception;
use GuzzleHttp\Client;
use putyourlightson\campaign\Campaign;
use yii\log\Logger;

/**
 * https://docs.aws.amazon.com/ses/latest/APIReference-V2/API_SendBulkEmail.html
 */
class AmazonSesBatchMailer extends BaseBatchMailer
{
    public const MAX_BATCH_SIZE = 50;
    public const TEMPLATE_NAME = 'craft-campaign-template';

    public ?string $accessKey = null;
    public ?string $secretKey = null;
    public ?string $region = null;

    private ?Client $client = null;
    private bool $templateExists = false;

    /**
     * @inheritdoc
     */
    public function sendBatchEmails(array $emails): void
    {
        $this->ensureTemplateExists();

        $bulkEmailEntries = [];
        foreach ($emails as $email) {
            $bulkEmailEntries[] = [
                'Destination' => [
                    'ToAddresses' => [$email->to],
                ],
                'ReplacementEmailContent' => [
                    'ReplacementTemplate' => [
                        'ReplacementTemplateData' => json_encode([
                            'Subject' => [
                                'Data' => $email->subject,
                                'Charset' => 'UTF-8',
                            ],
                            'Body' => [
                                'Text' => [
                                    'Data' => $email->plaintextBody,
                                    'Charset' => 'UTF-8',
                                ],
                                'Html' => [
                                    'Data' => $email->htmlBody,
                                    'Charset' => 'UTF-8',
                                ],
                            ],
                        ]),
                    ],
                ],
            ];
        }

        while (count($bulkEmailEntries) > 0) {
            $email = $bulkEmailEntries[0];
            $bulkEmailEntriesBatch = array_splice($bulkEmailEntries, 0, self::MAX_BATCH_SIZE);

            try {
                $response = $this->getClient()->post('/v2/email/outbound-bulk-emails', [
                    'json' => [
                        'FromEmailAddress' => $email->fromEmail,
                        'ReplyToAddresses' => [$email->replyToEmail],
                        'BulkEmailEntries' => $bulkEmailEntriesBatch,
                        'DefaultContent' => [
                            'Template' => [
                                'TemplateName' => self::TEMPLATE_NAME,
                            ],
                        ],
                    ],
                ]);
            } catch (Exception $exception) {
                Campaign::$plugin->log($exception->getMessage(), [], Logger::LEVEL_ERROR);
            }
        }
    }

    private function getClient(): Client
    {
        if ($this->client !== null) {
            return $this->client;
        }

        $this->client = $this->createClient([
            'base_uri' => 'https://email.' . $this->region . '.amazonaws.com',
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'auth' => [
                $this->accessKey,
                $this->secretKey,
            ],
        ]);

        return $this->client;
    }

    private function ensureTemplateExists(): void
    {
        if ($this->templateExists === true) {
            return;
        }

        try {
            $response = $this->getClient()->get('/v2/email/templates/' . self::TEMPLATE_NAME);

            if ($response->getStatusCode() === 404) {
                $this->createTemplate();
            }

            $this->templateExists = true;
        } catch (Exception $exception) {
            Campaign::$plugin->log($exception->getMessage(), [], Logger::LEVEL_ERROR);
        }
    }

    private function createTemplate(): void
    {
        try {
            $this->getClient()->post('/v2/email/templates', [
                'json' => [
                    'TemplateName' => self::TEMPLATE_NAME,
                    'TemplateContent' => [
                        'Subject' => '',
                        'Text' => '',
                        'Html' => '',
                    ],
                ],
            ]);
        } catch (Exception $exception) {
            Campaign::$plugin->log($exception->getMessage(), [], Logger::LEVEL_ERROR);
        }
    }
}
