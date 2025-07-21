<?php

/**
 * Tests verifying pending contacts.
 */

use putyourlightson\campaign\Campaign;

afterAll(function() {
    Craft::$app->gc->deleteAllTrashed = true;
    Craft::$app->gc->hardDeleteElements();
});

test('A pending contact creates a contact', function() {
    $mailingList = createMailingList();
    $pendingContactRecord = createPendingContactRecord($mailingList->id);

    Campaign::$plugin->pendingContacts->verifyPendingContact($pendingContactRecord->pid);
    $contact = Campaign::$plugin->contacts->getContactByEmail($pendingContactRecord->email);

    expect($contact)
        ->not->toBeNull();
});

test('A pending contact for a soft-deleted contact restores the contact', function() {
    $mailingList = createMailingList();
    $contact = createContact();
    Craft::$app->elements->deleteElement($contact);
    $pendingContactRecord = createPendingContactRecord($mailingList->id, $contact->email);

    Campaign::$plugin->pendingContacts->verifyPendingContact($pendingContactRecord->pid);
    $contact = Campaign::$plugin->contacts->getContactById($contact->id);

    expect($contact)
        ->not->toBeNull();
});

test('A pending contact soft-deletes the pending contact', function() {
    $mailingList = createMailingList();
    $pendingContactRecord = createPendingContactRecord($mailingList->id);

    $pendingContact = Campaign::$plugin->pendingContacts->verifyPendingContact($pendingContactRecord->pid);

    expect(Campaign::$plugin->pendingContacts->getIsPendingContactTrashed($pendingContact->pid))
        ->toBeTrue();
});

test('A soft-deleted pending does nothing', function() {
    $mailingList = createMailingList();
    $pendingContactRecord = createPendingContactRecord($mailingList->id);
    $pendingContactRecord->softDelete();

    $pendingContact = Campaign::$plugin->pendingContacts->verifyPendingContact($pendingContactRecord->pid);

    expect($pendingContact)
        ->toBeNull();
});
