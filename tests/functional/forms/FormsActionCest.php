<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\tests\functional\forms;

use Codeception\Test\Unit;
use Craft;
use DateInterval;
use DateTime;
use FunctionalTester;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\helpers\StringHelper;
use putyourlightson\campaign\models\PendingContactModel;
use putyourlightson\campaign\records\MailingListTypeRecord;
use putyourlightson\campaign\records\PendingContactRecord;
use UnitTester;
use yii\swiftmailer\Message;

/**
 * FormsActionCest
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.10.0
 */

class FormsActionCest
{
    // Properties
    // =========================================================================

    // Public methods
    // =========================================================================

    public function _before(FunctionalTester $I)
    {
    }

    public function subscribe(FunctionalTester $I)
    {
        $I->amOnPage('/subscribe');

        $I->see('Subscribe to our Mailing List');
//
//        Craft::$app->getConfig()->getGeneral()->requireUserAgentAndIpForSession = false;
//        $I->submitForm('#userform', [
//            'action' => 'users/impersonate',
//            'redirect' => Craft::$app->getSecurity()->hashData(UrlHelper::cpUrl('dashboard'))
//        ]);
//
//        $I->see('Dashboard');
//        $I->see('Logged in');
//
//        $I->assertSame(
//            (string)$this->activeUser->id,
//            (string)$user = Craft::$app->getUser()->getId()
//        );
    }
}
