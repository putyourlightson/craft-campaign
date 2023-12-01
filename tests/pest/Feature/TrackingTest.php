<?php

/**
 * Tests tracking contact interactions with sendouts.
 */

use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\records\LinkRecord;

beforeEach(function() {
    Campaign::$plugin->sync->registerUserEvents();
});

test('Sendout opens are tracked on the campaign', function() {
    $contact = createContact();
    $sendout = createSendoutWithSubscribedContact($contact);

    // Test multiple events simultaneously
    Campaign::$plugin->tracker->open($contact, $sendout);
    Campaign::$plugin->tracker->open($contact, $sendout);
    Campaign::$plugin->tracker->open($contact, $sendout);

    $campaign = Campaign::$plugin->campaigns->getCampaignById($sendout->campaignId);

    expect($campaign->opened)
        ->toBe(1)
        ->and($campaign->opens)
        ->toBe(3);
});

test('Sendout clicks are tracked on the campaign and link and register an open', function() {
    $contact = createContact();
    $sendout = createSendoutWithSubscribedContact($contact);
    $linkRecord = createLinkRecord($sendout->campaignId);

    // Test multiple events simultaneously
    Campaign::$plugin->tracker->click($contact, $sendout, $linkRecord);
    Campaign::$plugin->tracker->click($contact, $sendout, $linkRecord);
    Campaign::$plugin->tracker->click($contact, $sendout, $linkRecord);

    $campaign = Campaign::$plugin->campaigns->getCampaignById($sendout->campaignId);
    $linkRecord = LinkRecord::findOne($linkRecord->id);

    expect($campaign->clicked)
        ->toBe(1)
        ->and($campaign->clicks)
        ->toBe(3)
        ->and($linkRecord->clicked)
        ->toBe(1)
        ->and($linkRecord->clicks)
        ->toBe(3)
        ->and($campaign->opened)
        ->toBe(1)
        ->and($campaign->opens)
        ->toBe(1);
});

test('Unsubscribes are tracked on the campaign and update the contact’s mailing list status', function() {
    $contact = createContact();
    $sendout = createSendoutWithSubscribedContact($contact);

    Campaign::$plugin->tracker->unsubscribe($contact, $sendout);

    $campaign = Campaign::$plugin->campaigns->getCampaignById($sendout->campaignId);
    $status = $contact->getMailingListSubscriptionStatus($sendout->mailingListIds[0]);

    expect($campaign->unsubscribed)
        ->toBe(1)
        ->and($status)
        ->toBe('unsubscribed');
});
