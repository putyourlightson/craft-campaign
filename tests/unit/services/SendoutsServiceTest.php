<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaigntests\unit\services;

use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\elements\SendoutElement;
use putyourlightson\campaigntests\fixtures\CampaignsFixture;
use putyourlightson\campaigntests\fixtures\CampaignTypesFixture;
use putyourlightson\campaigntests\fixtures\ContactsFixture;
use putyourlightson\campaigntests\fixtures\MailingListsFixture;
use putyourlightson\campaigntests\fixtures\SendoutsFixture;
use putyourlightson\campaigntests\unit\BaseUnitTest;

/**
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.10.0
 */

class SendoutsServiceTest extends BaseUnitTest
{
    // Fixtures
    // =========================================================================

    /**
     * @return array
     */
    public function _fixtures(): array
    {
        return [
            'mailingLists' => [
                'class' => MailingListsFixture::class
            ],
            'contacts' => [
                'class' => ContactsFixture::class
            ],
            'campaigns' => [
                'class' => CampaignsFixture::class
            ],
            'sendouts' => [
                'class' => SendoutsFixture::class
            ],
        ];
    }

    // Public methods
    // =========================================================================

    public function testSendEmailSent()
    {
        $sendout = SendoutElement::find()->one();
        $contact = ContactElement::find()->one();
        $mailingList = MailingListElement::find()->one();

        Campaign::$plugin->sendouts->sendEmail($sendout, $contact, $mailingList->id);

        // Assert that the message recipient is correct
        $this->assertArrayHasKey($contact->email, $this->message->getTo());

        // Assert that the message subject is correct
        $this->assertEquals($sendout->subject, $this->message->getSubject());

        // Assert that the message body contains the tracking image
        $this->assertStringContainsStringIgnoringCase('campaign/t/open', $this->message->getSwiftMessage()->toString());
    }

    public function testSendEmailFailed()
    {
        $sendout = SendoutElement::find()->one();
        $contact = ContactElement::find()->one();
        $mailingList = MailingListElement::find()->one();

        $sendout->sendStatus = SendoutElement::STATUS_SENDING;

        // Mocked mailer in `BaseUnitTest` will fail with this email subject
        $sendout->subject = 'Fail';

        // Set send attempts to 1
        Campaign::$plugin->getSettings()->maxSendAttempts = 1;

        Campaign::$plugin->sendouts->sendEmail($sendout, $contact, $mailingList->id);

        // Assert that the message was not sent
        $this->assertNull($this->message);

        // Assert that the send status is failed
        $this->assertEquals($sendout->sendStatus, SendoutElement::STATUS_FAILED);
    }

    public function testSendEmailDuplicate()
    {
        $sendout = SendoutElement::find()->one();
        $contact = ContactElement::find()->one();
        $mailingList = MailingListElement::find()->one();

        $sendout->sendStatus = SendoutElement::STATUS_SENDING;

        Campaign::$plugin->sendouts->sendEmail($sendout, $contact, $mailingList->id);

        // Reset message and resend
        $this->message = null;
        Campaign::$plugin->sendouts->sendEmail($sendout, $contact, $mailingList->id);

        // Assert that the message is null
        $this->assertNull($this->message);
    }

    public function testSendNotificationSent()
    {
        $sendout = SendoutElement::find()->one();
        $sendout->sendStatus = SendoutElement::STATUS_SENT;

        Campaign::$plugin->sendouts->sendNotification($sendout);

        // Assert that the message recipient is correct
        $this->assertArrayHasKey($sendout->notificationEmailAddress, $this->message->getTo());

        // Assert that the message subject is correct
        $this->assertStringContainsString('completed', $this->message->getSubject());
    }

    public function testSendNotificationFailed()
    {
        $sendout = SendoutElement::find()->one();
        $sendout->sendStatus = SendoutElement::STATUS_FAILED;

        Campaign::$plugin->sendouts->sendNotification($sendout);

        // Assert that the message recipient is correct
        $this->assertArrayHasKey($sendout->notificationEmailAddress, $this->message->getTo());

        // Assert that the message subject is correct
        $this->assertStringContainsStringIgnoringCase('failed', $this->message->getSubject());
    }
}
