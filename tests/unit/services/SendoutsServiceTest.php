<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaigntests\unit\services;

use Craft;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\elements\SendoutElement;
use putyourlightson\campaigntests\unit\BaseUnitTest;
use putyourlightson\campaigntests\fixtures\CampaignsFixture;
use putyourlightson\campaigntests\fixtures\ContactsFixture;
use putyourlightson\campaigntests\fixtures\MailingListsFixture;
use putyourlightson\campaigntests\fixtures\SendoutsFixture;

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

        $this->sendout = SendoutElement::find()->title('Sendout 1')->one();
        $this->contact = ContactElement::find()->email('contact@contacts.com')->one();
        $this->mailingList = MailingListElement::find()->one();

        Campaign::$plugin->edition = Campaign::EDITION_PRO;

        // Set sendout's mailing list
        $this->sendout->mailingListIds = $this->mailingList->id;

        // Subscribe contacts (including trashed) to all mailing lists
        $mailingLists = MailingListElement::find()->all();

        foreach (ContactElement::find()->trashed(null)->all() as $contact) {
            foreach ($mailingLists as $mailingList) {
                Campaign::$plugin->mailingLists->addContactInteraction($contact, $mailingList, 'subscribed');
            }
        }
    }

    // Public methods
    // =========================================================================

    public function testGetPendingRecipients()
    {
        $count = Campaign::$plugin->sendouts->getPendingRecipientCount($this->sendout);

        // Assert that the number of pending recipients is correct
        $this->assertEquals(1, $count);
    }

    public function testGetPendingRecipientsRemoved()
    {
        Campaign::$plugin->mailingLists->deleteContactSubscription($this->contact, $this->mailingList);

        $pendingRecipients = Campaign::$plugin->sendouts->getPendingRecipients($this->sendout);

        $this->assertEmpty($pendingRecipients);
    }

    public function testGetPendingRecipientsUnsubscribed()
    {
        Campaign::$plugin->mailingLists->addContactInteraction($this->contact, $this->mailingList, 'unsubscribed');

        $pendingRecipients = Campaign::$plugin->sendouts->getPendingRecipients($this->sendout);

        $this->assertEmpty($pendingRecipients);
    }

    public function testGetPendingRecipientsSoftDeleted()
    {
        Craft::$app->getElements()->deleteElement($this->contact);

        $pendingRecipients = Campaign::$plugin->sendouts->getPendingRecipients($this->sendout);

        $this->assertEmpty($pendingRecipients);
    }

    public function testGetPendingRecipientsHardDeleted()
    {
        Craft::$app->getElements()->deleteElement($this->contact, true);

        $pendingRecipients = Campaign::$plugin->sendouts->getPendingRecipients($this->sendout);

        $this->assertEmpty($pendingRecipients);
    }

    public function testGetPendingRecipientsAutomated()
    {
        $sendout = SendoutElement::find()->sendoutType('automated')->one();

        // Expect this to return 0 since the contact subscribed less than the delay
        $pendingRecipients = Campaign::$plugin->sendouts->getPendingRecipients($sendout);

        // Assert that the number of pending recipients is correct
        $this->assertEmpty($pendingRecipients);
    }

    public function testQueuePendingSendouts()
    {
        $sendoutCount = SendoutElement::find()->sendoutType('regular')->count();
        $count = Campaign::$plugin->sendouts->queuePendingSendouts();

        // Assert that the number of queued sendouts is correct
        $this->assertEquals($sendoutCount, $count);

        $queuedSendouts = SendoutElement::find()->status(SendoutElement::STATUS_QUEUED)->count();

        // Assert that the sendout status is correct
        $this->assertEquals($sendoutCount, $queuedSendouts);

        // Assert that the job was pushed onto the queue
        $this->assertTrue(Craft::$app->getQueue()->getHasWaitingJobs());
    }

    public function testSendEmailSent()
    {
        $this->sendout->sendStatus = SendoutElement::STATUS_SENDING;

        Campaign::$plugin->sendouts->sendEmail($this->sendout, $this->contact, $this->mailingList->id);

        // Assert that the message was sent
        $this->assertNotNull($this->message);

        // Assert that the message recipient is correct
        $this->assertArrayHasKey($this->contact->email, $this->message->getTo());

        // Assert that the message subject is correct
        $this->assertEquals($this->sendout->subject, $this->message->getSubject());

        // Get the message body, removing email body nastiness
        $body = $this->message->getSwiftMessage()->toString();
        $body = str_replace(['3D', "=\r\n"], '', $body);

        // Assert that the message body contains a link with the correct IDs
        $this->assertStringContainsStringIgnoringCase('&amp;cid='.$this->contact->cid, $body);
        $this->assertStringContainsStringIgnoringCase('&amp;sid='.$this->sendout->sid, $body);
        $this->assertStringContainsStringIgnoringCase('&amp;lid=', $body);

        // Assert that the message body contains the tracking image
        $this->assertStringContainsStringIgnoringCase('campaign/t/open', $body);
    }

    public function testSendEmailFailed()
    {
        $this->sendout->sendStatus = SendoutElement::STATUS_SENDING;

        // Mocked mailer in `BaseUnitTest` will fail with this email subject
        $this->sendout->subject = 'Fail';

        // Set send attempts and fails to 1
        Campaign::$plugin->getSettings()->maxSendAttempts = 1;
        Campaign::$plugin->getSettings()->maxSendFailsAllowed = 1;

        Campaign::$plugin->sendouts->sendEmail($this->sendout, $this->contact, $this->mailingList->id);

        // Assert that the message was not sent
        $this->assertNull($this->message);

        // Assert that the number of fails is 1
        $this->assertEquals(1, $this->sendout->fails);

        // Assert that the send status is failed
        $this->assertEquals(SendoutElement::STATUS_FAILED, $this->sendout->sendStatus);
    }

    public function testSendEmailTemplateError()
    {
        $sendout2 = SendoutElement::find()->title('Sendout 2')->one();
        $sendout2->sendStatus = SendoutElement::STATUS_SENDING;

        Campaign::$plugin->sendouts->sendEmail($sendout2, $this->contact, $this->mailingList->id);

        // Clean the output buffer, since we forced a template error
        ob_end_clean();

        // Assert that the send status is failed
        $this->assertEquals(SendoutElement::STATUS_FAILED, $sendout2->sendStatus);
    }

    public function testSendEmailDuplicate()
    {
        $this->sendout->sendStatus = SendoutElement::STATUS_SENDING;

        Campaign::$plugin->sendouts->sendEmail($this->sendout, $this->contact, $this->mailingList->id);

        // Assert that the message is not null
        $this->assertNotNull($this->message);

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

        // Assert that the message is not null
        $this->assertNotNull($this->message);

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

        // Assert that the message is not null
        $this->assertNotNull($this->message);

        // Assert that the message recipient is correct
        $this->assertArrayHasKey($this->sendout->notificationEmailAddress, $this->message->getTo());

        // Assert that the message subject is correct
        $this->assertStringContainsStringIgnoringCase('failed', $this->message->getSubject());
    }
}
