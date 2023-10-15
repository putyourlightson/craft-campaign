<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaigntests\unit\services;

use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\CampaignElement;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\SendoutElement;
use putyourlightson\campaign\records\ContactCampaignRecord;
use putyourlightson\campaigntests\fixtures\CampaignsFixture;
use putyourlightson\campaigntests\fixtures\ContactsFixture;
use putyourlightson\campaigntests\fixtures\SendoutsFixture;
use putyourlightson\campaigntests\unit\BaseUnitTest;

/**
 * @since 2.9.1
 */
class CampaignsServiceTest extends BaseUnitTest
{
    public function _fixtures(): array
    {
        return [
            'campaigns' => [
                'class' => CampaignsFixture::class,
            ],
            'contacts' => [
                'class' => ContactsFixture::class,
            ],
            'sendouts' => [
                'class' => SendoutsFixture::class,
            ],
        ];
    }

    protected CampaignElement $campaign;
    protected ContactElement $contact;
    protected SendoutElement $sendout;

    protected function _before(): void
    {
        parent::_before();

        $this->campaign = CampaignElement::find()->one();
        $this->contact = ContactElement::find()->one();
        $this->sendout = SendoutElement::find()->one();
    }

    public function testAddContactInteraction(): void
    {
        $contactCampaignRecord = new ContactCampaignRecord([
            'campaignId' => $this->campaign->id,
            'contactId' => $this->contact->id,
            'sendoutId' => $this->sendout->id,
        ]);
        $contactCampaignRecord->save();

        Campaign::$plugin->campaigns->addContactInteraction($this->contact, $this->sendout, 'clicked');
        Campaign::$plugin->campaigns->addContactInteraction($this->contact, $this->sendout, 'unsubscribed');
        Campaign::$plugin->campaigns->addContactInteraction($this->contact, $this->sendout, 'bounced');

        /** @var ContactCampaignRecord $contactCampaignRecord */
        $contactCampaignRecord = ContactCampaignRecord::find()->one();

        $this->assertNotNull($contactCampaignRecord->opened);
        $this->assertNotNull($contactCampaignRecord->unsubscribed);
        $this->assertNotNull($contactCampaignRecord->bounced);
        $this->assertEquals(1, $contactCampaignRecord->opens);
        $this->assertEquals(1, $contactCampaignRecord->clicks);

        $campaign = CampaignElement::find()->one();

        $this->assertNotNull($campaign->opened);
        $this->assertEquals(1, $campaign->opens);
        $this->assertEquals(1, $campaign->clicks);
        $this->assertEquals(1, $campaign->unsubscribed);
        $this->assertEquals(1, $campaign->bounced);
    }
}
