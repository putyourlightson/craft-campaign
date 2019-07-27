<?php

namespace putyourlightson\campaign\tests\unit;

use Codeception\Test\Unit;

use Craft;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\records\MailingListTypeRecord;
use UnitTester;

class FormsTest extends Unit
{
    // Properties
    // =========================================================================

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

        $this->contact = new ContactElement([
            'email' => 'test@test.com',
        ]);
        Craft::$app->getElements()->saveElement($this->contact);

        $mailingListType = new MailingListTypeRecord([
            'name' => 'Test',
            'handle' => 'test',
            'siteId' => Craft::$app->getSites()->getPrimarySite()->id,
        ]);
        $mailingListType->save();

        $this->mailingList = new MailingListElement([
            'mailingListTypeId' => $mailingListType->id,
            'title' => 'Test',
        ]);
        Craft::$app->getElements()->saveElement($this->mailingList);

        // Subscribe contact to mailing list
        Campaign::$plugin->forms->subscribeContact($this->contact, $this->mailingList);
    }

    // Public methods
    // =========================================================================

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
