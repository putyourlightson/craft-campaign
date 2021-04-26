<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaigntests\unit\services;

use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\models\ImportModel;
use putyourlightson\campaigntests\base\BaseUnitTest;
use putyourlightson\campaigntests\fixtures\ContactsFixture;
use putyourlightson\campaigntests\fixtures\MailingListsFixture;

/**
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.16.6
 */

class ImportsServiceTest extends BaseUnitTest
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
        ];
    }

    // Public methods
    // =========================================================================

    public function testImportRow()
    {
        $contact = ContactElement::find()->one();
        $mailingList = MailingListElement::find()->one();

        $import = new ImportModel([
            'emailFieldIndex' => 'email',
            'mailingListId' => $mailingList->id,
            'forceSubscribe' => false,
        ]);

        $row = ['email' => $contact->email];

        Campaign::$plugin->mailingLists->addContactInteraction($contact, $mailingList, 'unsubscribed');

        Campaign::$plugin->imports->importRow($import, $row, 1);

        // Assert that contact is unsubscribed from the mailing list
        $this->assertEquals($contact->getMailingListSubscriptionStatus($mailingList->id), 'unsubscribed');

        $import->forceSubscribe = true;
        Campaign::$plugin->imports->importRow($import, $row, 1);

        // Assert that contact is subscribed to the mailing list
        $this->assertEquals($contact->getMailingListSubscriptionStatus($mailingList->id), 'subscribed');
    }
}
