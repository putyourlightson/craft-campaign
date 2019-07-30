<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\tests\unit\controllers;

use Craft;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\tests\unit\BaseControllerTest;
use yii\web\NotFoundHttpException;

/**
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.10.0
 */

class FormsControllerTest extends BaseControllerTest
{
    // Public methods
    // =========================================================================

    public function testSubscribeSuccess()
    {
        $this->mailingListType->subscribeVerificationRequired = false;
        Campaign::$plugin->mailingListTypes->saveMailingListType($this->mailingListType, false);

        $this->runActionWithParams('forms/subscribe', [
            'mailingList' => $this->mailingList->slug,
            'email' => $this->email,
        ]);

        // Assert that no email was sent
        $this->assertNull($this->message);
    }

    public function testSubscribeVerify()
    {
        $this->mailingListType->subscribeVerificationRequired = true;
        Campaign::$plugin->mailingListTypes->saveMailingListType($this->mailingListType);

        $this->runActionWithParams('forms/subscribe', [
            'mailingList' => $this->mailingList->slug,
            'email' => $this->email,
        ]);

        // Assert that the message subject is correct
        $this->assertEquals($this->mailingListType->subscribeVerificationEmailSubject, $this->message->getSubject());

        // Assert that the message body contains the correct controller action ID
        $this->assertStringContainsString('campaign/forms/verify-subscribe', $this->message->getSwiftMessage()->toString());
    }

    public function testUpdateContactSuccess()
    {
        $response = $this->runActionWithParams('forms/update-contact', [
            'cid' => $this->contact->cid,
            'uid' => $this->contact->uid,
        ]);

        // Assert that the response is not null
        $this->assertNotNull($response);
    }

    public function testUpdateContactFail()
    {
        // Expect an exception
        $this->tester->expectException(NotFoundHttpException::class, function() {
            $this->runActionWithParams('forms/update-contact', [
                'cid' => $this->contact->cid,
                'uid' => '',
            ]);
        });
    }

    public function testVerifySubscribeSuccess()
    {
        $response = $this->runActionWithParams('forms/verify-subscribe', [
            'pid' => $this->pendingContact->pid,
        ]);

        // Assert that the response is not null
        $this->assertNotNull($response);
    }

    public function testVerifySubscribeFail()
    {
        // Expect an exception
        $this->tester->expectException(NotFoundHttpException::class, function() {
            $this->runActionWithParams('forms/verify-subscribe', [
                'pid' => '',
            ]);
        });
    }
}
