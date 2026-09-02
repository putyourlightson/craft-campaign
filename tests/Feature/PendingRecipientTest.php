<?php

use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\SendoutElement;

/**
 * Tests calculating the pending recipient count of sendouts.
 */

test('A sendout’s pending recipient count equals the sum of its mailing list subscribers', function() {
    $sendout = createSendoutWithSubscribedContact();

    expect(Campaign::$plugin->sendouts->getPendingRecipients($sendout))
        ->toHaveLength(1);
});

test('A sendout’s pending recipient count does not include complained contacts', function() {
    $sendout = createSendoutWithSubscribedContact(attributes: ['complained' => new DateTime()]);

    expect(Campaign::$plugin->sendouts->getPendingRecipientCount($sendout))
        ->toBe(0);
});

test('A sendout’s pending recipient count does not include bounced contacts', function() {
    $sendout = createSendoutWithSubscribedContact(attributes: ['bounced' => new DateTime()]);

    expect(Campaign::$plugin->sendouts->getPendingRecipientCount($sendout))
        ->toBe(0);
});

test('A sendout’s pending recipient count does not include blocked contacts', function() {
    $sendout = createSendoutWithSubscribedContact(attributes: ['blocked' => new DateTime()]);

    expect(Campaign::$plugin->sendouts->getPendingRecipientCount($sendout))
        ->toBe(0);
});

// https://github.com/putyourlightson/craft-campaign/issues/523
test('A sendout’s pending recipient mailing list is the first one that is subscribed, regardless of previously subscribe mailing lists', function() {
    $contact = createContact();
    $mailingList1 = createMailingList();
    Campaign::$plugin->mailingLists->addContactInteraction($contact, $mailingList1, 'subscribed');
    Campaign::$plugin->mailingLists->addContactInteraction($contact, $mailingList1, 'unsubscribed');
    $sendout = createSendoutWithSubscribedContact($contact);
    $mailingList2 = $contact->getSubscribedMailingLists()[0];
    $sendout->mailingListIds = [$mailingList1->id, $mailingList2->id];

    $pendingRecipients = Campaign::$plugin->sendouts->getPendingRecipients($sendout);
    expect($pendingRecipients)
        ->toHaveLength(1)
        ->and($pendingRecipients[0]['mailingListId'])
        ->toBe($mailingList2->id);
});

// https://github.com/putyourlightson/craft-campaign/issues/489
test('A sendout matches all selected segments by default', function() {
    $contact1 = createContact();
    $contact2 = createContact();
    $mailingList = createMailingList();
    Campaign::$plugin->mailingLists->addContactInteraction($contact1, $mailingList, 'subscribed');
    Campaign::$plugin->mailingLists->addContactInteraction($contact2, $mailingList, 'subscribed');
    $segment1 = createSegmentMatchingEmail($contact1->email);
    $segment2 = createSegmentMatchingEmail($contact2->email);
    $sendout = createSendout(mailingListIds: [$mailingList->id], attributes: [
        'segmentIds' => [$segment1->id, $segment2->id],
    ]);
    $sendout = SendoutElement::find()->id($sendout->id)->one();

    expect($sendout->segmentMatch)
        ->toBe(SendoutElement::SEGMENT_MATCH_ALL)
        ->and(Campaign::$plugin->sendouts->getPendingRecipients($sendout))
        ->toHaveLength(0);
});

test('A sendout can match any selected segment', function() {
    $contact1 = createContact();
    $contact2 = createContact();
    $mailingList = createMailingList();
    Campaign::$plugin->mailingLists->addContactInteraction($contact1, $mailingList, 'subscribed');
    Campaign::$plugin->mailingLists->addContactInteraction($contact2, $mailingList, 'subscribed');
    $segment1 = createSegmentMatchingEmail($contact1->email);
    $segment2 = createSegmentMatchingEmail($contact1->email);
    $segment3 = createSegmentMatchingEmail($contact2->email);
    $sendout = createSendout(mailingListIds: [$mailingList->id], attributes: [
        'segmentIds' => [$segment1->id, $segment2->id, $segment3->id],
        'segmentMatch' => SendoutElement::SEGMENT_MATCH_ANY,
    ]);

    expect(Campaign::$plugin->sendouts->getPendingRecipients($sendout))
        ->toHaveLength(2);
});

test('Matching any selected segment does not add contacts outside the sendout audience', function() {
    $subscribedContact = createContact();
    $unsubscribedContact = createContact();
    $mailingList = createMailingList();
    Campaign::$plugin->mailingLists->addContactInteraction($subscribedContact, $mailingList, 'subscribed');
    $segment1 = createSegmentMatchingEmail($subscribedContact->email);
    $segment2 = createSegmentMatchingEmail($unsubscribedContact->email);
    $sendout = createSendout(mailingListIds: [$mailingList->id], attributes: [
        'segmentIds' => [$segment1->id, $segment2->id],
        'segmentMatch' => SendoutElement::SEGMENT_MATCH_ANY,
    ]);

    $recipients = Campaign::$plugin->sendouts->getPendingRecipients($sendout);
    expect($recipients)
        ->toHaveLength(1)
        ->and($recipients[0]['contactId'])
        ->toBe($subscribedContact->id);
});
