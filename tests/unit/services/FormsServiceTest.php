<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\tests\unit\services;

use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\tests\unit\BaseUnitTest;

/**
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.10.0
 */

class FormsServiceTest extends BaseUnitTest
{
    // Public methods
    // =========================================================================

    public function testSendVerifySubscribeEmail()
    {
        Campaign::$plugin->forms->sendVerifySubscribeEmail($this->pendingContact, $this->mailingList);

        // Assert that the message recipient is correct
        $this->assertArrayHasKey($this->pendingContact->email, $this->message->getTo());

        // Assert that the message subject is correct
        $this->assertEquals($this->mailingListType->subscribeVerificationEmailSubject, $this->message->getSubject());

        // Assert that the message body contains the correct controller action ID
        $this->assertStringContainsString('campaign/forms/verify-subscribe', $this->message->getSwiftMessage()->toString());
    }

    public function testSendVerifyUnsubscribeEmail()
    {
        Campaign::$plugin->forms->sendVerifyUnsubscribeEmail($this->contact, $this->mailingList);

        // Assert that the message recipient is correct
        $this->assertArrayHasKey($this->contact->email, $this->message->getTo());

        // Assert that the message subject is correct
        $this->assertEquals($this->mailingListType->unsubscribeVerificationEmailSubject, $this->message->getSubject());

        // Assert that the message body contains the correct controller action ID
        $this->assertStringContainsString('campaign/forms/verify-unsubscribe', $this->message->getSwiftMessage()->toString());
    }

    public function testSubscribeContact()
    {
        // Assert that contact is subscribed to 1 mailing list
        $this->assertEquals($this->contact->getSubscribedCount(), 1);

        // Assert that contact is subscribed the correct mailing list
        $this->assertEquals($this->contact->getSubscribedMailingLists()[0]->id, $this->mailingList->id);
    }

    public function testUnsubscribeContact()
    {
        // Unsubscribe contact from mailing list
        Campaign::$plugin->forms->unsubscribeContact($this->contact, $this->mailingList);

        // Assert that contact is subscribed to 0 mailing lists
        $this->assertEquals($this->contact->getSubscribedCount(), 0);
    }

    public function testUpdateContact()
    {
        $lastActivity = $this->contact->lastActivity;

        // Update contact
        Campaign::$plugin->forms->updateContact($this->contact);

        // Assert that contact activity has been updated
        $this->assertGreaterThan($lastActivity, $this->contact->lastActivity);
    }
}
