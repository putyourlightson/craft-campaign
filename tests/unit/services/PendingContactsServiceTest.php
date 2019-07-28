<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\tests\unit\services;

use Codeception\Test\Unit;
use Craft;
use DateInterval;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\helpers\StringHelper;
use putyourlightson\campaign\models\PendingContactModel;
use putyourlightson\campaign\records\MailingListTypeRecord;
use putyourlightson\campaign\records\PendingContactRecord;
use UnitTester;

/**
 * PendingContactsServiceTest
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.10.0
 */

class PendingContactsServiceTest extends Unit
{
    // Properties
    // =========================================================================

    /**
     * @var UnitTester
     */
    protected $tester;

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
        $success = Campaign::$plugin->pendingContacts->savePendingContact($this->pendingContact);

        // Assert that the pending contact was saved
        $this->assertTrue($success);
    }

    public function testVerifyPendingContact()
    {
        Campaign::$plugin->pendingContacts->savePendingContact($this->pendingContact);

        Campaign::$plugin->pendingContacts->verifyPendingContact($this->pendingContact->pid);

        // Assert that the contact was created
        $this->assertNotNull(Campaign::$plugin->contacts->getContactByEmail($this->pendingContact->email));

        $pendingContactRecord = PendingContactRecord::find()
            ->where(['pid' => $this->pendingContact->pid])
            ->one();

        // Assert that the pending contact was deleted
        $this->assertNull($pendingContactRecord);
    }

    public function testPurgeExpiredPendingContacts()
    {
        Campaign::$plugin->pendingContacts->savePendingContact($this->pendingContact);

        // Set duration to 1 second
        Campaign::$plugin->getSettings()->purgePendingContactsDuration = 1;

        // Sleep for 2 seconds
        sleep(2);

        Campaign::$plugin->pendingContacts->purgeExpiredPendingContacts();

        $pendingContactCount = PendingContactRecord::find()
            ->where(['pid' => $this->pendingContact->pid])
            ->count();

        // Assert that the pending contact was deleted
        $this->assertEquals(0, $pendingContactCount);
    }
}
