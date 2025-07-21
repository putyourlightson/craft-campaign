<?php

use putyourlightson\campaign\Campaign;

/**
 * Tests importing contacts into mailing lists.
 */

test('Importing a new contact creates a subscribed contact', function() {
    $mailingList = createMailingList();
    $contact = createContact();
    $import = createImport($mailingList->id);

    Campaign::$plugin->imports->importRow($import, ['email' => $contact->email], 1);

    expect($contact->getMailingListSubscriptionStatus($mailingList->id))
        ->toBe('subscribed');
});

test('Importing a new contact with unsubscribed enabled creates an unsubscribed contact', function() {
    $mailingList = createMailingList();
    $contact = createContact();
    $import = createImport($mailingList->id);
    $import->unsubscribe = true;

    Campaign::$plugin->imports->importRow($import, ['email' => $contact->email], 1);

    expect($contact->getMailingListSubscriptionStatus($mailingList->id))
        ->toBe('unsubscribed');
});

test('Importing an already unsubscribed contact results in it remaining unsubscribed', function() {
    $mailingList = createMailingList();
    $contact = createContact();
    $import = createImport($mailingList->id);

    Campaign::$plugin->mailingLists->addContactInteraction($contact, $mailingList, 'unsubscribed');
    Campaign::$plugin->imports->importRow($import, ['email' => $contact->email], 1);

    expect($contact->getMailingListSubscriptionStatus($mailingList->id))
        ->toBe('unsubscribed');
});

test('Importing an already unsubscribed contact with force subscribe enabled results in it becoming subscribed', function() {
    $mailingList = createMailingList();
    $contact = createContact();
    $import = createImport($mailingList->id);
    $import->forceSubscribe = true;

    Campaign::$plugin->mailingLists->addContactInteraction($contact, $mailingList, 'unsubscribed');
    Campaign::$plugin->imports->importRow($import, ['email' => $contact->email], 1);

    expect($contact->getMailingListSubscriptionStatus($mailingList->id))
        ->toBe('subscribed');
});
