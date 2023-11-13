<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaigntests\unit\controllers;

use Craft;
use craft\web\Response;
use EllipticCurve\Ecdsa;
use EllipticCurve\PrivateKey;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaigntests\fixtures\ContactsFixture;

/**
 * @since 1.19.0
 */
class WebhookControllerTest extends BaseControllerTest
{
    public function _fixtures(): array
    {
        return [
            'contacts' => [
                'class' => ContactsFixture::class,
            ],
        ];
    }

    /**
     * @var ContactElement
     */
    protected ContactElement $contact;

    /**
     * @var array
     */
    protected array $mailersendRequestBody;

    /**
     * @var array
     */
    protected array $mailgunRequestBody;

    /**
     * @var array
     */
    protected array $postmarkRequestParams;

    /**
     * @var array
     */
    protected array $sendgridRequestBody;

    protected function _before(): void
    {
        parent::_before();

        $this->_getContact();
        $this->contact->bounced = null;
        Craft::$app->elements->saveElement($this->contact);

        Campaign::$plugin->settings->validateWebhookRequests = true;

        $this->mailersendRequestBody = [
            'type' => 'activity.hard_bounced',
            'data' => [
                'email' => [
                    'from' => 'test@email.com',
                    'recipient' => [
                        'email' => $this->contact->email,
                    ],
                ],
            ],
        ];

        $this->mailgunRequestBody = [
            'signature' => [
                'timestamp' => time(),
                'token' => 'abcdefg',
                'signature' => 'fake',
            ],
            'event-data' => [
                'event' => 'failed',
                'severity' => 'permanent',
                'reason' => 'bounce',
                'recipient' => $this->contact->email,
            ],
        ];

        $this->postmarkRequestParams = [
            'key' => Campaign::$plugin->getSettings()->apiKey,
            'RecordType' => 'Bounce',
            'Type' => 'HardBounce',
            'Email' => $this->contact->email,
        ];

        $this->sendgridRequestBody = [
            [
                'event' => 'bounce',
                'email' => $this->contact->email,
            ],
        ];
    }

    protected function _getContact(): void
    {
        $this->contact = ContactElement::find()
            ->email('contact@contacts.com')
            ->status(null)
            ->one();
    }

    public function testMailersendVerificationFailure(): void
    {
        Campaign::$plugin->getSettings()->mailersendWebhookSigningSecret = 'aBcDeFgHiJkLmNoP123';

        Craft::$app->getRequest()->setRawBody(json_encode($this->mailersendRequestBody));

        /** @var Response $response */
        $response = $this->runActionWithParams('webhook/mailersend', [
            'key' => Campaign::$plugin->getSettings()->apiKey,
        ]);

        $this->assertEquals('Signature could not be authenticated.', $response->data);
    }

    public function testMailersendVerificationSuccess(): void
    {
        $this->assertEquals(ContactElement::STATUS_ACTIVE, $this->contact->getStatus());

        $signingSecret = 'aBcDeFgHiJkLmNoP123';
        Campaign::$plugin->getSettings()->mailersendWebhookSigningSecret = $signingSecret;

        $body = json_encode($this->mailersendRequestBody);
        Craft::$app->getRequest()->setRawBody($body);
        Craft::$app->request->headers->set(
            'Signature',
            hash_hmac('sha256', $body, $signingSecret),
        );

        /** @var Response $response */
        $response = $this->runActionWithParams('webhook/mailersend', [
            'key' => Campaign::$plugin->getSettings()->apiKey,
        ]);

        $this->assertEquals(200, $response->statusCode);

        $this->_getContact();
        $this->assertEquals(ContactElement::STATUS_BOUNCED, $this->contact->getStatus());
    }

    public function testMailgunVerificationFailure(): void
    {
        Campaign::$plugin->getSettings()->mailgunWebhookSigningKey = 'key-aBcDeFgHiJkLmNoP';

        Craft::$app->getRequest()->setRawBody(json_encode($this->mailgunRequestBody));

        /** @var Response $response */
        $response = $this->runActionWithParams('webhook/mailgun', [
            'key' => Campaign::$plugin->getSettings()->apiKey,
        ]);

        $this->assertEquals('Signature could not be authenticated.', $response->data);
    }

    public function testMailgunVerificationSuccess(): void
    {
        $this->assertEquals(ContactElement::STATUS_ACTIVE, $this->contact->getStatus());

        $signingKey = 'key-aBcDeFgHiJkLmNoP';
        Campaign::$plugin->getSettings()->mailgunWebhookSigningKey = $signingKey;

        $this->mailgunRequestBody['signature']['signature'] = hash_hmac(
            'sha256',
            $this->mailgunRequestBody['signature']['timestamp'] . $this->mailgunRequestBody['signature']['token'],
            $signingKey
        );

        Craft::$app->getRequest()->setRawBody(json_encode($this->mailgunRequestBody));

        /** @var Response $response */
        $response = $this->runActionWithParams('webhook/mailgun', [
            'key' => Campaign::$plugin->getSettings()->apiKey,
        ]);

        $this->assertEquals(200, $response->statusCode);

        $this->_getContact();
        $this->assertEquals(ContactElement::STATUS_BOUNCED, $this->contact->getStatus());
    }

    public function testMailgunLegacy(): void
    {
        Campaign::$plugin->getSettings()->validateWebhookRequests = false;
        $this->assertEquals(ContactElement::STATUS_ACTIVE, $this->contact->getStatus());

        /** @var Response $response */
        $response = $this->runActionWithParams('webhook/mailgun', [
            'key' => Campaign::$plugin->getSettings()->apiKey,
            'event' => 'bounced',
            'recipient' => $this->contact->email,
        ]);

        $this->assertEquals(200, $response->statusCode);

        $this->_getContact();
        $this->assertEquals(ContactElement::STATUS_BOUNCED, $this->contact->getStatus());
    }

    public function testPostmarkIpAddressFail(): void
    {
        /** @var Response $response */
        $response = $this->runActionWithParams('webhook/postmark', $this->postmarkRequestParams);

        $this->assertEquals('IP address not allowed.', $response->data);
    }

    public function testPostmarkIpAddressSuccess(): void
    {
        $this->assertEquals(ContactElement::STATUS_ACTIVE, $this->contact->getStatus());

        $ip = '1.2.3.4';
        $_SERVER['REMOTE_ADDR'] = $ip;
        Campaign::$plugin->getSettings()->postmarkAllowedIpAddresses = [$ip];

        /** @var Response $response */
        $response = $this->runActionWithParams('webhook/postmark', $this->postmarkRequestParams);

        $this->assertEquals(200, $response->statusCode);

        $this->_getContact();
        $this->assertEquals(ContactElement::STATUS_BOUNCED, $this->contact->getStatus());
    }

    public function testSendgridVerificationFailure(): void
    {
        Campaign::$plugin->getSettings()->sendgridWebhookVerificationKey = 'aBcDeFgHiJkLmNoP123==';

        Craft::$app->getRequest()->setRawBody(json_encode($this->sendgridRequestBody));

        /** @var Response $response */
        $response = $this->runActionWithParams('webhook/sendgrid', [
            'key' => Campaign::$plugin->getSettings()->apiKey,
        ]);

        $this->assertEquals('Signature could not be authenticated.', $response->data);
    }

    public function testSendgridVerificationSuccess(): void
    {
        $this->assertEquals(ContactElement::STATUS_ACTIVE, $this->contact->getStatus());

        $body = json_encode($this->sendgridRequestBody);
        Craft::$app->getRequest()->setRawBody($body);

        $privateKey = PrivateKey::fromString('MHQCAQEEIODvZuS34wFbt0X53+P5EnSj6tMjfVK01dD1dgDH02RzoAcGBSuBBAAKoUQDQgAE/nvHu/SQQaos9TUljQsUuKI15Zr5SabPrbwtbfT/408rkVVzq8vAisbBRmpeRREXj5aog/Mq8RrdYy75W9q/Ig==');
        Campaign::$plugin->getSettings()->sendgridWebhookVerificationKey = $privateKey->publicKey()->toString();

        $timestamp = strtotime('now');
        $timestampedBody = $timestamp . $body;
        Craft::$app->request->headers->set(
            'X-Twilio-Email-Event-Webhook-Signature',
            Ecdsa::sign($timestampedBody, $privateKey)->toBase64(),
        );
        Craft::$app->request->headers->set(
            'X-Twilio-Email-Event-Webhook-Timestamp',
            $timestamp,
        );

        /** @var Response $response */
        $response = $this->runActionWithParams('webhook/sendgrid', [
            'key' => Campaign::$plugin->getSettings()->apiKey,
        ]);

        $this->assertEquals(200, $response->statusCode);

        $this->_getContact();
        $this->assertEquals(ContactElement::STATUS_BOUNCED, $this->contact->getStatus());
    }
}
