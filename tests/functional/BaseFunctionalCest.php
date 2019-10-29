<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaigntests\functional;

use Craft;
use FunctionalTester;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\CampaignElement;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\elements\SendoutElement;
use putyourlightson\campaign\helpers\StringHelper;
use putyourlightson\campaign\models\CampaignTypeModel;
use putyourlightson\campaign\models\MailingListTypeModel;
use putyourlightson\campaign\models\PendingContactModel;
use putyourlightson\campaign\records\CampaignTypeRecord;
use putyourlightson\campaign\records\MailingListTypeRecord;
use yii\swiftmailer\Message;

/**
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.10.0
 */

class BaseFunctionalCest
{
    // Properties
    // =========================================================================

    /**
     * @var FunctionalTester
     */
    protected $tester;

    /**
     * @var CampaignTypeModel
     */
    protected $campaignType;

    /**
     * @var CampaignElement
     */
    protected $campaign;

    /**
     * @var MailingListTypeModel
     */
    protected $mailingListType;

    /**
     * @var MailingListElement
     */
    protected $mailingList;

    /**
     * @var ContactElement
     */
    protected $contact;

    /**
     * @var PendingContactModel
     */
    protected $pendingContact;

    /**
     * @var SendoutElement
     */
    protected $sendout;

    /**
     * @var Message
     */
    protected $message;

    // Protected methods
    // =========================================================================

    /**
     * Set up the class properties before running all tests
     */
    public function _before(FunctionalTester $I)
    {
//        $this->campaignType = new CampaignTypeModel([
//            'name' => 'Campaign Type Title',
//            'handle' => 'campaignTypeHandle',
//            'siteId' => Craft::$app->getSites()->getPrimarySite()->id,
//            'htmlTemplate' => 'html',
//            'plaintextTemplate' => 'plaintext',
//            'queryStringParameters' => 'source=campaign-plugin&medium=email&campaign={{ campaign.title }}',
//        ]);
//        $campaignTypeRecord = new CampaignTypeRecord();
//        $campaignTypeRecord->setAttributes($this->campaignType->getAttributes(), false);
//        $campaignTypeRecord->save();
//
//        $this->campaign = new CampaignElement([
//            'title' => 'Campaign Title',
//            'campaignTypeId' => $campaignTypeRecord->id,
//        ]);
//        Craft::$app->getElements()->saveElement($this->campaign);
//
//        $this->mailingListType = new MailingListTypeModel([
//            'name' => 'Mailing List Type Name',
//            'handle' => 'mailingListTypeHandle',
//            'siteId' => Craft::$app->getSites()->getPrimarySite()->id,
//            'subscribeVerificationEmailSubject' => 'Subscribe Verification Email Subject',
//            'unsubscribeVerificationEmailSubject' => 'Unsubscribe Verification Email Subject',
//        ]);
//        $mailingListTypeRecord = new MailingListTypeRecord();
//        $mailingListTypeRecord->setAttributes($this->mailingListType->getAttributes(), false);
//        $mailingListTypeRecord->save();
//
//        $this->mailingList = new MailingListElement([
//            'mailingListTypeId' => $mailingListTypeRecord->id,
//            'title' => 'Mailing List Title',
//            'slug' => 'mailingListSlug',
//        ]);
//        Craft::$app->getElements()->saveElement($this->mailingList);
//
//        $this->contact = new ContactElement([
//            'email' => 'email@contact.com',
//        ]);
//        Craft::$app->getElements()->saveElement($this->contact);
//
//        // Subscribe contact to mailing list
//        Campaign::$plugin->forms->subscribeContact($this->contact, $this->mailingList);
//
//        $this->pendingContact = new PendingContactModel([
//            'email' => 'email@pendingcontact.com',
//            'mailingListId' => $this->mailingList->id,
//            'pid' => StringHelper::uniqueId('p'),
//            'fieldData' => [],
//        ]);
//
//        $this->sendout = new SendoutElement([
//            'sendoutType' => 'regular',
//            'title' => 'Sendout Title',
//            'campaignId' => $this->campaign->id,
//            'subject' => 'Sendout Subject',
//            'mailingListIds' => [$this->mailingList->id],
//            'fromName' => 'From Name',
//            'fromEmail' => 'email@sendout.com',
//            'notificationEmailAddress' => 'email@notification.com',
//        ]);
//        Craft::$app->getElements()->saveElement($this->sendout);

        // Mock the mailer
        $I->mockMethods(
            Campaign::$plugin,
            'mailer',
            [
                'send' => function (Message $message) {
                    if ($message->getSubject() == 'Fail') {
                        return false;
                    }

                    $this->message = $message;

                    return true;
                }
            ]
        );
    }
}
