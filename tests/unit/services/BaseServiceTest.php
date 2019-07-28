<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\tests\unit\services;

use Codeception\Test\Unit;
use Craft;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\helpers\StringHelper;
use putyourlightson\campaign\models\MailingListTypeModel;
use putyourlightson\campaign\models\PendingContactModel;
use putyourlightson\campaign\records\MailingListTypeRecord;
use UnitTester;
use yii\swiftmailer\Message;

/**
 * BaseServiceTest
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.10.0
 */

class BaseServiceTest extends Unit
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
     * @var MailingListTypeModel
     */
    protected $mailingListType;

    /**
     * @var PendingContactModel
     */
    protected $pendingContact;

    /**
     * @var Message
     */
    protected $message;

    // Protected methods
    // =========================================================================

    protected function _before()
    {
        parent::_before();

        $this->mailingListType = new MailingListTypeModel([
            'name' => 'Test',
            'handle' => 'test',
            'siteId' => Craft::$app->getSites()->getPrimarySite()->id,
            'subscribeVerificationEmailSubject' => 'Subscribe Verification Email Subject',
            'unsubscribeVerificationEmailSubject' => 'Unsubscribe Verification Email Subject',
        ]);
        $mailingListTypeRecord = new MailingListTypeRecord();
        $mailingListTypeRecord->setAttributes($this->mailingListType->getAttributes(), false);
        $mailingListTypeRecord->save();

        $this->mailingList = new MailingListElement([
            'mailingListTypeId' => $mailingListTypeRecord->id,
            'title' => 'Test',
        ]);
        Craft::$app->getElements()->saveElement($this->mailingList);

        $this->contact = new ContactElement([
            'email' => 'test@test.com',
        ]);
        Craft::$app->getElements()->saveElement($this->contact);

        // Subscribe contact to mailing list
        Campaign::$plugin->forms->subscribeContact($this->contact, $this->mailingList);

        $this->pendingContact = new PendingContactModel([
            'email' => 'pending@test.com',
            'mailingListId' => $this->mailingList->id,
            'pid' => StringHelper::uniqueId('p'),
            'fieldData' => [],
        ]);

        // Mock the mailer
        $this->tester->mockMethods(
            Campaign::$plugin,
            'mailer',
            [
                'send' => function (Message $message) {
                    $this->message = $message;
                    return true;
                }
            ]
        );
    }
}
