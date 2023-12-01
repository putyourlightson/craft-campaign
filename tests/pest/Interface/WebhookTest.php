<?php

use craft\web\Response;
use EllipticCurve\Ecdsa;
use EllipticCurve\PrivateKey;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\ContactElement;

/**
 * Tests the webhook API endpoints.
 */

beforeEach(function() {
    Campaign::$plugin->settings->validateWebhookRequests = true;
    Craft::$app->request->setRawBody('');
    $_POST[Craft::$app->request->methodParam] = 'post';
});

test('A signed MailerSend bounce request marks the contact as bounced and returns a success', function() {
    $contact = createContact();
    $signingSecret = 'aBcDeFgHiJkLmNoP123';
    Campaign::$plugin->settings->mailersendWebhookSigningSecret = $signingSecret;

    $body = json_encode(getMailerSendBody($contact->email));
    Craft::$app->request->setRawBody($body);
    Craft::$app->request->headers->set(
        'Signature',
        hash_hmac('sha256', $body, $signingSecret),
    );

    $response = runActionWithParams('webhook/mailersend', [
        'key' => Campaign::$plugin->settings->apiKey,
    ]);

    $contact = ContactElement::findOne($contact->id);

    expect($response->statusCode)
        ->toBe(200)
        ->and($contact->getStatus())
        ->toBe(ContactElement::STATUS_BOUNCED);
});

test('An unsigned MailerSend request returns an error', function() {
    $contact = createContact();
    Campaign::$plugin->settings->mailersendWebhookSigningSecret = 'aBcDeFgHiJkLmNoP123';

    $body = json_encode(getMailerSendBody($contact->email));
    Craft::$app->request->setRawBody($body);

    $response = runActionWithParams('webhook/mailersend', [
        'key' => Campaign::$plugin->settings->apiKey,
    ]);

    expect($response->statusCode)
        ->toBe(400);
});

test('A signed Mailgun bounce request marks the contact as bounced and returns a success', function() {
    $contact = createContact();
    $signingKey = 'key-aBcDeFgHiJkLmNoP';
    Campaign::$plugin->settings->mailgunWebhookSigningKey = $signingKey;

    $body = getMailgunBody($contact);
    $body['signature']['signature'] = hash_hmac(
        'sha256',
        $body['signature']['timestamp'] . $body['signature']['token'],
        $signingKey
    );
    Craft::$app->request->setRawBody(json_encode($body));

    $response = runActionWithParams('webhook/mailgun', [
        'key' => Campaign::$plugin->settings->apiKey,
    ]);

    $contact = ContactElement::findOne($contact->id);

    expect($response->statusCode)
        ->toBe(200)
        ->and($contact->getStatus())
        ->toBe(ContactElement::STATUS_BOUNCED);
});

test('An unsigned Mailgun request returns an error', function() {
    $contact = createContact();
    Campaign::$plugin->settings->mailgunWebhookSigningKey = 'key-aBcDeFgHiJkLmNoP';

    Craft::$app->request->setRawBody(json_encode(getMailgunBody($contact)));

    $response = runActionWithParams('webhook/mailgun', [
        'key' => Campaign::$plugin->settings->apiKey,
    ]);

    expect($response->statusCode)
        ->toBe(400);
});

test('A legacy Mailgun bounce request marks the contact as bounced and returns a success', function() {
    $contact = createContact();
    Campaign::$plugin->settings->validateWebhookRequests = false;

    $response = runActionWithParams('webhook/mailgun', [
        'key' => Campaign::$plugin->settings->apiKey,
        'event' => 'bounced',
        'recipient' => $contact->email,
    ]);

    $contact = ContactElement::findOne($contact->id);

    expect($response->statusCode)
        ->toBe(200)
        ->and($contact->getStatus())
        ->toBe(ContactElement::STATUS_BOUNCED);
});

test('A Postmark bounce request with an allowed IP address marks the contact as bounced and returns a success', function() {
    $contact = createContact();
    $_SERVER['REMOTE_ADDR'] = '1.2.3.4';
    Campaign::$plugin->settings->postmarkAllowedIpAddresses = [$_SERVER['REMOTE_ADDR']];

    $response = runActionWithParams('webhook/postmark',
        getPostmarkRequestParams($contact->email),
    );

    $contact = ContactElement::findOne($contact->id);

    expect($response->statusCode)
        ->toBe(200)
        ->and($contact->getStatus())
        ->toBe(ContactElement::STATUS_BOUNCED);
});

test('A Postmark bounce request with a disallowed IP address returns an error', function() {
    $contact = createContact();
    $_SERVER['REMOTE_ADDR'] = '4.3.2.1';

    $response = runActionWithParams('webhook/postmark',
        getPostmarkRequestParams($contact->email),
    );

    expect($response->statusCode)
        ->toBe(400);
});

test('A signed SendGrid bounce request marks the contact as bounced and returns a success', function() {
    $contact = createContact();

    $body = json_encode(getSendgridRequestBody($contact->email));
    Craft::$app->request->setRawBody($body);

    $privateKey = PrivateKey::fromString('MHQCAQEEIODvZuS34wFbt0X53+P5EnSj6tMjfVK01dD1dgDH02RzoAcGBSuBBAAKoUQDQgAE/nvHu/SQQaos9TUljQsUuKI15Zr5SabPrbwtbfT/408rkVVzq8vAisbBRmpeRREXj5aog/Mq8RrdYy75W9q/Ig==');
    Campaign::$plugin->settings->sendgridWebhookVerificationKey = $privateKey->publicKey()->toString();

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

    $response = runActionWithParams('webhook/sendgrid', [
        'key' => Campaign::$plugin->settings->apiKey,
    ]);

    $contact = ContactElement::findOne($contact->id);

    expect($response->statusCode)
        ->toBe(200)
        ->and($contact->getStatus())
        ->toBe(ContactElement::STATUS_BOUNCED);
});

test('An unsigned SendGrid request returns an error', function() {
    $contact = createContact();

    $body = json_encode(getSendgridRequestBody($contact->email));
    Craft::$app->request->setRawBody($body);

    $response = runActionWithParams('webhook/mailersend', [
        'key' => Campaign::$plugin->settings->apiKey,
    ]);

    expect($response->statusCode)
        ->toBe(400);
});

function runActionWithParams(string $action, array $params = []): Response
{
    Craft::$app->request->setBodyParams($params);
    Craft::$app->response->statusCode = null;

    return Campaign::$plugin->runAction($action);
}

function getMailerSendBody(string $email): array
{
    return [
        'type' => 'activity.hard_bounced',
        'data' => [
            'email' => [
                'from' => 'test@email.com',
                'recipient' => [
                    'email' => $email,
                ],
            ],
        ],
    ];
}

function getMailgunBody(string $email): array
{
    return [
        'signature' => [
            'timestamp' => time(),
            'token' => 'abcdefg',
            'signature' => 'fake',
        ],
        'event-data' => [
            'event' => 'failed',
            'severity' => 'permanent',
            'reason' => 'bounce',
            'recipient' => $email,
        ],
    ];
}

function getPostmarkRequestParams(string $email): array
{
    return [
        'key' => Campaign::$plugin->settings->apiKey,
        'RecordType' => 'Bounce',
        'Type' => 'HardBounce',
        'Email' => $email,
    ];
}

function getSendgridRequestBody(string $email): array
{
    return [
        [
            'event' => 'bounce',
            'email' => $email,
        ],
    ];
}
