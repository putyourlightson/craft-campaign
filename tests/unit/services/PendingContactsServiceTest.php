<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\tests\unit\services;

use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\records\PendingContactRecord;
use putyourlightson\campaign\tests\unit\BaseUnitTest;

/**
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.10.0
 */

class PendingContactsServiceTest extends BaseUnitTest
{
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

        // Sleep for 2 seconds, to be sure to be sure (less causes the assertion to fail sometimes)
        sleep(2);

        Campaign::$plugin->pendingContacts->purgeExpiredPendingContacts();

        $pendingContactCount = PendingContactRecord::find()
            ->where(['pid' => $this->pendingContact->pid])
            ->count();

        // Assert that the pending contact was deleted
        $this->assertEquals(0, $pendingContactCount);
    }
}
