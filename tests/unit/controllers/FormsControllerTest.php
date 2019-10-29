<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaigntests\unit\controllers;

use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\records\PendingContactRecord;
use putyourlightson\campaigntests\fixtures\ContactsFixture;
use putyourlightson\campaigntests\fixtures\MailingListsFixture;
use putyourlightson\campaigntests\fixtures\PendingContactsFixture;
use putyourlightson\campaigntests\unit\BaseControllerTest;
use yii\web\NotFoundHttpException;

/**
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.10.0
 */

class FormsControllerTest extends BaseControllerTest
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

    public function testSubscribeSuccess()
    {
        $mailingList = MailingListElement::find()->mailingListType('mailingListType1')->one();

        $this->runActionWithParams('forms/subscribe', [
            'mailingList' => $mailingList->slug,
            'email' => $this->email,
        ]);

        // Assert that no email was sent
        $this->assertNull($this->message);
    }

    public function testSubscribeVerify()
    {
        $mailingList = MailingListElement::find()->mailingListType('mailingListType2')->one();

        $this->runActionWithParams('forms/subscribe', [
            'mailingList' => $mailingList->slug,
            'email' => $this->email,
        ]);

        // Assert that the message subject is correct
        $this->assertEquals($mailingList->mailingListType->subscribeVerificationEmailSubject, $this->message->getSubject());

        // Assert that the message body contains the correct controller action ID
        $this->assertStringContainsString('campaign/forms/verify-subscribe', $this->message->getSwiftMessage()->toString());
    }

    public function testUpdateContactSuccess()
    {
        $contact = ContactElement::find()->one();

        $response = $this->runActionWithParams('forms/update-contact', [
            'cid' => $contact->cid,
            'uid' => $contact->uid,
        ]);

        // Assert that the response is not null
        $this->assertNotNull($response);
    }

    public function testUpdateContactFail()
    {
        // Expect an exception
        $this->tester->expectThrowable(NotFoundHttpException::class, function() {
            $contact = ContactElement::find()->one();

            $this->runActionWithParams('forms/update-contact', [
                'cid' => $contact->cid,
                'uid' => '',
            ]);
        });
    }

    public function testVerifySubscribeSuccess()
    {
        $pendingContact = PendingContactRecord::find()->one();

        $response = $this->runActionWithParams('forms/verify-subscribe', [
            'pid' => $pendingContact->pid,
        ]);

        // Assert that the response is not null
        $this->assertNotNull($response);
    }

    public function testVerifySubscribeFail()
    {
        // Expect an exception
        $this->tester->expectThrowable(NotFoundHttpException::class, function() {
            $this->runActionWithParams('forms/verify-subscribe', []);
        });
    }
}
