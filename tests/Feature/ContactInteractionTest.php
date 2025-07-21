<?php

/**
 * Tests contact interactions with campaigns.
 */

use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\records\ContactCampaignRecord;
use putyourlightson\campaign\records\LinkRecord;

beforeEach(function() {
    Campaign::$plugin->sync->registerUserEvents();
});

test('A contact clicking a link in a sendout registers interactions', function() {
    $contact = createContact();
    $sendout = createSendoutWithSubscribedContact($contact);
    $linkRecord = createLinkRecord($sendout->campaignId);

    Campaign::$plugin->campaigns->addContactInteraction($contact, $sendout, 'clicked', $linkRecord);

    /** @var ContactCampaignRecord $contactCampaignRecord */
    $contactCampaignRecord = ContactCampaignRecord::find()->one();
    $linkRecord = LinkRecord::findOne($linkRecord->id);

    expect($contactCampaignRecord->clicked)
        ->not->toBeNull()
        ->and($contactCampaignRecord->opened)
        ->not->toBeNull()
        ->and($contactCampaignRecord->opens)
        ->toBe(1)
        ->and($contactCampaignRecord->clicks)
        ->toBe(1)
        ->and($linkRecord->clicked)
        ->not->toBeNull()
        ->and($linkRecord->clicks)
        ->toBe(1);
});
