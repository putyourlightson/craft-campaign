<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\tests\unit\services;

use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\SendoutElement;

/**
 * SendoutsServiceTest
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.10.0
 */

class SendoutsServiceTest extends BaseServiceTest
{
    // Public methods
    // =========================================================================

    public function testSendEmailSent()
    {
        $this->sendout->sendStatus = SendoutElement::STATUS_SENDING;

        Campaign::$plugin->sendouts->sendEmail($this->sendout, $this->contact, $this->mailingList->id);

        // Assert that the message recipient is correct
        $this->assertArrayHasKey($this->contact->email, $this->message->getTo());

        // Assert that the message subject is correct
        $this->assertEquals($this->sendout->subject, $this->message->getSubject());

        // Assert that the message body contains the tracking image
        $this->assertStringContainsStringIgnoringCase('campaign/t/open', $this->message->getSwiftMessage()->toString());
    }

    public function testSendEmailFailed()
    {
        $this->sendout->sendStatus = SendoutElement::STATUS_SENDING;
        $this->sendout->subject = 'Fail';

        // Set send attempts to 1 second
        Campaign::$plugin->getSettings()->maxSendAttempts = 1;

        Campaign::$plugin->sendouts->sendEmail($this->sendout, $this->contact, $this->mailingList->id);

        // Assert that the message was not sent
        $this->assertNull($this->message);

        // Assert that the send status is failed
        $this->assertEquals($this->sendout->sendStatus, SendoutElement::STATUS_FAILED);
    }

    public function testSendNotificationSent()
    {
        $this->sendout->sendStatus = SendoutElement::STATUS_SENT;

        Campaign::$plugin->sendouts->sendNotification($this->sendout);

        // Assert that the message recipient is correct
        $this->assertArrayHasKey($this->sendout->notificationEmailAddress, $this->message->getTo());

        // Assert that the message subject is correct
        $this->assertStringContainsString('completed', $this->message->getSubject());
    }

    public function testSendNotificationFailed()
    {
        $this->sendout->sendStatus = SendoutElement::STATUS_FAILED;

        Campaign::$plugin->sendouts->sendNotification($this->sendout);

        // Assert that the message recipient is correct
        $this->assertArrayHasKey($this->sendout->notificationEmailAddress, $this->message->getTo());

        // Assert that the message subject is correct
        $this->assertStringContainsStringIgnoringCase('failed', $this->message->getSubject());
    }
}
