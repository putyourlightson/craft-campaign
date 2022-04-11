<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaigntests\unit\services;

use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\records\PendingContactRecord;
use putyourlightson\campaigntests\fixtures\PendingContactsFixture;
use putyourlightson\campaigntests\unit\BaseUnitTest;

/**
 * @since 1.10.0
 */
class PendingContactsServiceTest extends BaseUnitTest
{
    public function _fixtures(): array
    {
        return [
            'pendingContacts' => [
                'class' => PendingContactsFixture::class,
            ],
        ];
    }

    public function testVerifyPendingContact()
    {
        $pendingContact = PendingContactRecord::find()->one();

        Campaign::$plugin->pendingContacts->verifyPendingContact($pendingContact->pid);

        // Assert that the contact was created
        $this->assertNotNull(Campaign::$plugin->contacts->getContactByEmail($pendingContact->email));

        $pendingContact = Campaign::$plugin->pendingContacts->getPendingContactByPid($pendingContact->pid);

        // Assert that the pending contact was deleted
        $this->assertNull($pendingContact);
    }

    public function testVerifyTrashedPendingContact()
    {
        $pendingContact = PendingContactRecord::find()->one();
        $pendingContact->softDelete();

        $this->assertNull(Campaign::$plugin->pendingContacts->getPendingContactByPid($pendingContact->pid));
        $this->assertTrue(Campaign::$plugin->pendingContacts->getIsPendingContactTrashed($pendingContact->pid));
    }

    public function testVerifyDeletedPendingContact()
    {
        $pendingContact = PendingContactRecord::find()->one();
        $pendingContact->delete();

        $this->assertNull(Campaign::$plugin->pendingContacts->getPendingContactByPid($pendingContact->pid));
        $this->assertFalse(Campaign::$plugin->pendingContacts->getIsPendingContactTrashed($pendingContact->pid));
    }

    public function testPurgeExpiredPendingContacts()
    {
        // Set duration to 1 second
        Campaign::$plugin->getSettings()->purgePendingContactsDuration = 1;

        // Sleep for 2 seconds, to be sure to be sure (less causes the assertion to fail sometimes)
        sleep(2);

        Campaign::$plugin->pendingContacts->purgeExpiredPendingContacts();

        $pendingContact = PendingContactRecord::find()->one();

        // Assert that the pending contact was deleted
        $this->assertNull($pendingContact);
    }
}
