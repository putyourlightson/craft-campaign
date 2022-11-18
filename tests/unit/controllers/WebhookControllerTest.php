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
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.19.0
 */

class WebhookControllerTest extends BaseControllerTest
{
    // Fixtures
    // =========================================================================

    /**
     * @return array
     */
    public function _fixtures(): array
    {
        return [
            'contacts' => [
                'class' => ContactsFixture::class
            ],
        ];
    }

    // Properties
    // =========================================================================

    /**
     * @var ContactElement
     */
    protected $contact;

    /**
     * @var array
     */
    protected $mailgunRequestBody;

    /**
     * @var array
     */
    protected $postmarkRequestParams;

    // Protected methods
    // =========================================================================

    protected function _before()
    {
        parent::_before();

        $this->_getContact();
        $this->contact->bounced = null;
        Craft::$app->elements->saveElement($this->contact);

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
    }

    protected function _getContact()
    {
        $this->contact = ContactElement::find()->status(null)->one();
    }

    // Public methods
    // =========================================================================

    public function testMailgunSignatureFail()
    {
        Campaign::$plugin->getSettings()->mailgunWebhookSigningKey = 'key-aBcDeFgHiJkLmNoP';

        Craft::$app->getRequest()->setRawBody(json_encode($this->mailgunRequestBody));

        /** @var Response $response */
        $response = $this->runActionWithParams('webhook/mailgun', [
            'key' => Campaign::$plugin->getSettings()->apiKey,
        ]);

        $this->assertEquals('Signature could not be authenticated.', $response->data['error']);
    }

    public function testMailgunSignatureSuccess()
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

        $this->assertEquals(200, $response->statusCode);

        $this->_getContact();
        $this->assertEquals(ContactElement::STATUS_BOUNCED, $this->contact->getStatus());
    }

    public function testPostmarkIpAddressFail()
    {
        /** @var Response $response */
        $response = $this->runActionWithParams('webhook/postmark', $this->postmarkRequestParams);

        $this->assertEquals('IP address not allowed.', $response->data['error']);
    }

    public function testPostmarkIpAddressSuccess()
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

        $this->assertEquals(['success' => true], $response->data);

        $this->_getContact();
        $this->assertEquals(ContactElement::STATUS_BOUNCED, $this->contact->getStatus());
    }
}
