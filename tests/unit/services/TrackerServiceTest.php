<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaigntests\unit\services;

use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\elements\SendoutElement;
use putyourlightson\campaign\records\ContactCampaignRecord;
use putyourlightson\campaign\records\LinkRecord;
use putyourlightson\campaigntests\fixtures\CampaignsFixture;
use putyourlightson\campaigntests\fixtures\ContactsFixture;
use putyourlightson\campaigntests\fixtures\LinksFixture;
use putyourlightson\campaigntests\fixtures\SendoutsFixture;
use putyourlightson\campaigntests\unit\BaseUnitTest;

/**
 * @since 1.12.2
 */
class TrackerServiceTest extends BaseUnitTest
{
    public function _fixtures(): array
    {
        return [
            'contacts' => [
                'class' => ContactsFixture::class,
            ],
            'campaigns' => [
                'class' => CampaignsFixture::class,
            ],
            'sendouts' => [
                'class' => SendoutsFixture::class,
            ],
            'links' => [
                'class' => LinksFixture::class,
            ],
        ];
    }

    /**
     * @var ContactElement
     */
    protected ContactElement $contact;

    /**
     * @var SendoutElement
     */
    protected SendoutElement $sendout;

    /**
     * @var MailingListElement
     */
    protected MailingListElement $mailingList;

    protected function _before(): void
    {
        $this->contact = ContactElement::find()->one();
        $this->sendout = SendoutElement::find()->one();
        $this->mailingList = $this->sendout->getMailingLists()[0];

        // Create contact campaign record
        $contactCampaignRecord = new ContactCampaignRecord([
            'contactId' => $this->contact->id,
            'campaignId' => $this->sendout->campaignId,
            'sendoutId' => $this->sendout->id,
            'mailingListId' => $this->mailingList->id,
        ]);
        $contactCampaignRecord->save();

        Campaign::$plugin->mailingLists->addContactInteraction($this->contact, $this->mailingList, 'subscribed');
    }

    public function testOpen(): void
    {
        // Test multiple events simultaneously
        Campaign::$plugin->tracker->open($this->contact, $this->sendout);
        Campaign::$plugin->tracker->open($this->contact, $this->sendout);
        Campaign::$plugin->tracker->open($this->contact, $this->sendout);

        $campaign = Campaign::$plugin->campaigns->getCampaignById($this->sendout->campaignId);

        $this->assertEquals(1, $campaign->opened);
        $this->assertEquals(3, $campaign->opens);
    }

    public function testClick(): void
    {
        $link = LinkRecord::find()->one();

        // Test multiple events simultaneously
        Campaign::$plugin->tracker->click($this->contact, $this->sendout, $link);
        Campaign::$plugin->tracker->click($this->contact, $this->sendout, $link);
        Campaign::$plugin->tracker->click($this->contact, $this->sendout, $link);

        $campaign = Campaign::$plugin->campaigns->getCampaignById($this->sendout->campaignId);
        $link = LinkRecord::findOne(['id' => $link->id]);

        $this->assertEquals(1, $campaign->clicked);
        $this->assertEquals(3, $campaign->clicks);
        $this->assertEquals(1, $link->clicked);
        $this->assertEquals(3, $link->clicks);
    }

    public function testUnsubscribe(): void
    {
        $status = $this->contact->getMailingListSubscriptionStatus($this->mailingList->id);

        // Assert that the contact is subscribed
        $this->assertEquals('subscribed', $status);

        Campaign::$plugin->tracker->unsubscribe($this->contact, $this->sendout);

        $status = $this->contact->getMailingListSubscriptionStatus($this->mailingList->id);

        // Assert that the contact is unsubscribed
        $this->assertEquals('unsubscribed', $status);
    }
}
