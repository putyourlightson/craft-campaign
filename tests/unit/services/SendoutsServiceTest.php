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

    // Properties
    // =========================================================================

    /**
     * @var SendoutElement
     */
    protected $sendout;

    /**
     * @var ContactElement
     */
    protected $contact;

    /**
     * @var MailingListElement
     */
    protected $mailingList;

    // Protected methods
    // =========================================================================

    protected function _before()
    {
        parent::_before();

        $this->sendout = SendoutElement::find()->one();
        $this->contact = ContactElement::find()->one();
        $this->mailingList = MailingListElement::find()->one();

        Campaign::$plugin->edition = Campaign::EDITION_PRO;
    }

    // Public methods
    // =========================================================================

    public function testGetPendingRecipients()
    {
        // Subscribe contacts (including trashed) to mailing list
        foreach (ContactElement::find()->trashed(null)->all() as $contact) {
            Campaign::$plugin->mailingLists->addContactInteraction($contact, $this->mailingList, 'subscribed');
        }

        $pendingRecipients = Campaign::$plugin->sendouts->getPendingRecipients($this->sendout);

        // Assert that the number of pending recipients is correct
        $this->assertEquals(1, count($pendingRecipients));

        $this->sendout->sendoutType = 'automated';

        $sendout = SendoutElement::find()->sendoutType('automated')->one();

        $pendingRecipients = Campaign::$plugin->sendouts->getPendingRecipients($sendout);

        // Assert that the number of pending recipients is correct
        $this->assertEquals(0, count($pendingRecipients));
    }

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

        // Mocked mailer in `BaseUnitTest` will fail with this email subject
        $this->sendout->subject = 'Fail';

        // Set send attempts to 1
        Campaign::$plugin->getSettings()->maxSendAttempts = 1;

        Campaign::$plugin->sendouts->sendEmail($this->sendout, $this->contact, $this->mailingList->id);

        // Assert that the message was not sent
        $this->assertNull($this->message);

        // Assert that the send status is failed
        $this->assertEquals($this->sendout->sendStatus, SendoutElement::STATUS_FAILED);
    }

    public function testSendEmailDuplicate()
    {
        $this->sendout->sendStatus = SendoutElement::STATUS_SENDING;

        Campaign::$plugin->sendouts->sendEmail($this->sendout, $this->contact, $this->mailingList->id);

        // Reset message and resend
        $this->message = null;
        Campaign::$plugin->sendouts->sendEmail($this->sendout, $this->contact, $this->mailingList->id);

        // Assert that the message is null
        $this->assertNull($this->message);
    }

    public function testSendNotificationSent()
    {
        $this->sendout = SendoutElement::find()->one();
        $this->sendout->sendStatus = SendoutElement::STATUS_SENT;

        Campaign::$plugin->sendouts->sendNotification($this->sendout);

        // Assert that the message recipient is correct
        $this->assertArrayHasKey($this->sendout->notificationEmailAddress, $this->message->getTo());

        // Assert that the message subject is correct
        $this->assertStringContainsString('completed', $this->message->getSubject());
    }

    public function testSendNotificationFailed()
    {
        $this->sendout = SendoutElement::find()->one();
        $this->sendout->sendStatus = SendoutElement::STATUS_FAILED;

        Campaign::$plugin->sendouts->sendNotification($this->sendout);

        // Assert that the message recipient is correct
        $this->assertArrayHasKey($this->sendout->notificationEmailAddress, $this->message->getTo());

        // Assert that the message subject is correct
        $this->assertStringContainsStringIgnoringCase('failed', $this->message->getSubject());
    }
}
