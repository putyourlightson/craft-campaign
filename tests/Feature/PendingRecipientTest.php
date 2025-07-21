<?php

use putyourlightson\campaign\Campaign;

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
