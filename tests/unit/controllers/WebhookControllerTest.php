<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaigntests\unit\controllers;

use Craft;
use craft\web\Response;
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

    protected function _before()
    {
        parent::_before();

        Craft::$app->request->getHeaders()->set('Accept', 'application/json');

        $this->_getContact();
        $this->contact->bounced = null;
        Craft::$app->elements->saveElement($this->contact);
    }

    protected function _getContact()
    {
        $this->contact = ContactElement::find()
            ->email('contact@contacts.com')
            ->status(null)
            ->one();
    }

    public function testMailgunSignature()
    {
        $this->assertEquals(ContactElement::STATUS_ACTIVE, $this->contact->getStatus());

        $timestamp = time();
        $token = 'abcdefg';
        $signingKey = 'key-aBcDeFgHiJkLmNoP';

        Campaign::$plugin->getSettings()->mailgunWebhookSigningKey = $signingKey;

        $eventData = [
            'event-data' => [
                'signature' => [
                    'timestamp' => $timestamp,
                    'token' => $token,
                    'signature' => 'fake',
                ],
                'event' => 'failed',
                'severity' => 'permanent',
                'reason' => 'bounce',
                'recipient' => $this->contact->email,
            ],
        ];

        Craft::$app->getRequest()->setRawBody(json_encode($eventData));

        /** @var Response $response */
        $response = $this->runActionWithParams('webhook/mailgun', [
            'key' => Campaign::$plugin->getSettings()->apiKey,
        ]);

        $this->assertEquals(['message' => 'Signature could not be authenticated.'], $response->data);

        $eventData['event-data']['signature']['signature'] = hash_hmac('sha256', $timestamp . $token, $signingKey);

        Craft::$app->getRequest()->setRawBody(json_encode($eventData));

        /** @var Response $response */
        $response = $this->runActionWithParams('webhook/mailgun', [
            'key' => Campaign::$plugin->getSettings()->apiKey,
        ]);

        $this->assertEquals([], $response->data);

        $this->_getContact();
        $this->assertEquals(ContactElement::STATUS_BOUNCED, $this->contact->getStatus());
    }

    public function testMailgunLegacy()
    {
        Campaign::$plugin->getSettings()->mailgunWebhookSigningKey = null;
        $this->assertEquals(ContactElement::STATUS_ACTIVE, $this->contact->getStatus());

        /** @var Response $response */
        $response = $this->runActionWithParams('webhook/mailgun', [
            'key' => Campaign::$plugin->getSettings()->apiKey,
            'event' => 'bounced',
            'recipient' => $this->contact->email,
        ]);

        $this->assertEquals([], $response->data);

        $this->_getContact();
        $this->assertEquals(ContactElement::STATUS_BOUNCED, $this->contact->getStatus());
    }

    public function testPostmarkIpAddresses()
    {
        $this->assertEquals(ContactElement::STATUS_ACTIVE, $this->contact->getStatus());

        $params = [
            'key' => Campaign::$plugin->getSettings()->apiKey,
            'RecordType' => 'Bounce',
            'Type' => 'HardBounce',
            'Email' => $this->contact->email,
        ];

        /** @var Response $response */
        $response = $this->runActionWithParams('webhook/postmark', $params);

        $this->assertEquals(['message' => 'IP address not allowed.'], $response->data);

        Campaign::$plugin->getSettings()->postmarkAllowedIpAddresses = [Craft::$app->getRequest()->getUserIP()];

        $response = $this->runActionWithParams('webhook/postmark', $params);

        $this->assertEquals([], $response->data);

        $this->_getContact();
        $this->assertEquals(ContactElement::STATUS_BOUNCED, $this->contact->getStatus());
    }

    public function testSendgrid()
    {
        $this->assertEquals(ContactElement::STATUS_ACTIVE, $this->contact->getStatus());

        $events = [
            [
                'event' => 'bounced',
                'email' => $this->contact->email,
            ],
        ];

        Craft::$app->getRequest()->setRawBody(json_encode($events));

        /** @var Response $response */
        $response = $this->runActionWithParams('webhook/sendgrid', [
            'key' => Campaign::$plugin->getSettings()->apiKey,
        ]);

        $this->assertEquals([], $response->data);

        $this->_getContact();
        $this->assertEquals(ContactElement::STATUS_BOUNCED, $this->contact->getStatus());
    }
}
