<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\tests\unit;

use Codeception\Test\Unit;
use Craft;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\helpers\StringHelper;
use putyourlightson\campaign\models\PendingContactModel;
use putyourlightson\campaign\records\MailingListTypeRecord;
use putyourlightson\campaign\records\PendingContactRecord;
use UnitTester;

/**
 * FormsTest
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.10.0
 */

class FormsTest extends Unit
{
    // Properties
    // =========================================================================

    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var ContactElement
     */
    protected $contact;

    /**
     * @var MailingListElement
     */
    protected $mailingList;

    /**
     * @var PendingContactModel
     */
    protected $pendingContact;

    // Protected methods
    // =========================================================================

    protected function _before()
    {
        parent::_before();

        $this->contact = new ContactElement([
            'email' => 'test@test.com',
        ]);
        Craft::$app->getElements()->saveElement($this->contact);

        $mailingListTypeRecord = new MailingListTypeRecord([
            'name' => 'Test',
            'handle' => 'test',
            'siteId' => Craft::$app->getSites()->getPrimarySite()->id,
        ]);
        $mailingListTypeRecord->save();

        $this->mailingList = new MailingListElement([
            'mailingListTypeId' => $mailingListTypeRecord->id,
            'title' => 'Test',
        ]);
        Craft::$app->getElements()->saveElement($this->mailingList);

        // Subscribe contact to mailing list
        Campaign::$plugin->forms->subscribeContact($this->contact, $this->mailingList);

        $this->pendingContact = new PendingContactModel([
            'email' => 'pending@test.com',
            'mailingListId' => $this->mailingList->id,
            'pid' => StringHelper::uniqueId('p'),
            'fieldData' => [],
        ]);
    }

    // Public methods
    // =========================================================================

    public function testSavePendingContact()
    {
        $success = Campaign::$plugin->forms->savePendingContact($this->pendingContact, $this->mailingList);

        // Assert that the pending contact was saved
        $this->assertTrue($success);
    }

    public function testVerifyPendingContact()
    {
        Campaign::$plugin->forms->savePendingContact($this->pendingContact, $this->mailingList);

        Campaign::$plugin->forms->verifyPendingContact($this->pendingContact->pid);

        // Assert that the contact was created
        $this->assertNotNull(Campaign::$plugin->contacts->getContactByEmail($this->pendingContact->email));

        $pendingContactRecord = PendingContactRecord::find()
            ->where(['pid' => $this->pendingContact->pid])
            ->one();

        // Assert that the pending contact was deleted
        $this->assertNull($pendingContactRecord);
    }

    public function testSendVerifySubscribeEmail()
    {
        // TODO: mock Campaign mailer component and assert email subject and body
        $success = Campaign::$plugin->forms->sendVerifySubscribeEmail($this->pendingContact, $this->mailingList);

        // Assert that the verification email was sent
        $this->assertTrue($success);
    }

    public function testSendVerifyUnsubscribeEmail()
    {
        // TODO: mock Campaign mailer component and assert email subject and body
        $success = Campaign::$plugin->forms->sendVerifyUnsubscribeEmail($this->contact, $this->mailingList);

        // Assert that the verification email was sent
        $this->assertTrue($success);
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
