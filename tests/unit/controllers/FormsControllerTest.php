<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\tests\unit\controllers;

use Craft;
use craft\web\Response;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\tests\unit\BaseControllerTest;

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

        Craft::$app->request->setBodyParams([
            'mailingList' => $this->mailingList->slug,
            'email' => $this->email,
        ]);
        Campaign::$plugin->runAction('forms/subscribe');

        // Assert that no email was sent
        $this->assertNull($this->message);
    }

    public function testSubscribeVerify()
    {
        $this->mailingListType->subscribeVerificationRequired = true;
        Campaign::$plugin->mailingListTypes->saveMailingListType($this->mailingListType);

        Craft::$app->request->setBodyParams([
            'mailingList' => $this->mailingList->slug,
            'email' => $this->email,
        ]);
        Campaign::$plugin->runAction('forms/subscribe');

        // Assert that the message subject is correct
        $this->assertEquals($this->mailingListType->subscribeVerificationEmailSubject, $this->message->getSubject());

        // Assert that the message body contains the correct controller action ID
        $this->assertStringContainsString('campaign/forms/verify-subscribe', $this->message->getSwiftMessage()->toString());
    }
}
