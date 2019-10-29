<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaigntests\unit\services;

use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\models\PendingContactModel;
use putyourlightson\campaign\records\PendingContactRecord;
use putyourlightson\campaigntests\fixtures\ContactsFixture;
use putyourlightson\campaigntests\fixtures\MailingListsFixture;
use putyourlightson\campaigntests\fixtures\PendingContactsFixture;
use putyourlightson\campaigntests\unit\BaseUnitTest;

/**
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.10.0
 */

class FormsServiceTest extends BaseUnitTest
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
            'pendingContacts' => [
                'class' => PendingContactsFixture::class
            ],
        ];
    }

    // Public methods
    // =========================================================================

    public function testSendVerifySubscribeEmail()
    {
        /** @var PendingContactModel $pendingContact */
        $pendingContact = PendingContactModel::populateModel(PendingContactRecord::find()->one(), false);
        $mailingList = Campaign::$plugin->mailingLists->getMailingListById($pendingContact->mailingListId);

        Campaign::$plugin->forms->sendVerifySubscribeEmail($pendingContact, $mailingList);

        // Assert that the message recipient is correct
        $this->assertArrayHasKey($pendingContact->email, $this->message->getTo());

        // Assert that the message subject is correct
        $this->assertEquals($mailingList->mailingListType->subscribeVerificationEmailSubject, $this->message->getSubject());

        // Assert that the message body contains the correct controller action ID
        $this->assertStringContainsString('campaign/forms/verify-subscribe', $this->message->getSwiftMessage()->toString());
    }

    public function testSendVerifyUnsubscribeEmail()
    {
        $contact = ContactElement::find()->one();
        $mailingList = MailingListElement::find()->mailingListType('mailingListType2')->one();

        Campaign::$plugin->forms->sendVerifyUnsubscribeEmail($contact, $mailingList);

        // Assert that the message recipient is correct
        $this->assertArrayHasKey($contact->email, $this->message->getTo());

        // Assert that the message subject is correct
        $this->assertEquals($mailingList->mailingListType->unsubscribeVerificationEmailSubject, $this->message->getSubject());

        // Assert that the message body contains the correct controller action ID
        $this->assertStringContainsString('campaign/forms/verify-unsubscribe', $this->message->getSwiftMessage()->toString());
    }

    public function testSubscribeUnsubscribeContact()
    {
        $contact = ContactElement::find()->one();
        $mailingList = MailingListElement::find()->one();

        // Subscribe contact to mailing list
        Campaign::$plugin->forms->subscribeContact($contact, $mailingList);

        // Assert that contact is subscribed to 1 mailing list
        $this->assertEquals($contact->getSubscribedCount(), 1);

        // Assert that contact is subscribed to the correct mailing list
        $this->assertEquals($contact->getSubscribedMailingLists()[0]->id, $mailingList->id);

        // Unsubscribe contact from mailing list
        Campaign::$plugin->forms->unsubscribeContact($contact, $mailingList);

        // Assert that contact is subscribed to 0 mailing lists
        $this->assertEquals($contact->getSubscribedCount(), 0);
    }

    public function testUpdateContact()
    {
        $contact = ContactElement::find()->one();

        // Update contact
        Campaign::$plugin->forms->updateContact($contact);

        $contact = Campaign::$plugin->contacts->getContactById($contact->id);

        // Assert that contact activity has been updated
        $this->assertNotNull($contact->lastActivity);
    }
}
