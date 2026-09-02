<?php

use craft\helpers\StringHelper;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\models\PendingContactModel;
use putyourlightson\campaign\records\ContactMailingListRecord;
use putyourlightson\campaign\services\FormsService;

/**
 * Tests interacting with contacts via forms.
 */

test('A verify subscribe email is sent to a pending contact on subscribe', function() {
    $mailingList = createMailingList();
    $pendingContactRecord = createPendingContactRecord($mailingList->id);
    $pendingContact = new PendingContactModel();
    $pendingContact->setAttributes($pendingContactRecord->getAttributes(), false);

    $formsService = Mockery::mock(FormsService::class)->makePartial();
    $formsService->shouldReceive('sendEmail')
        ->withSomeOfArgs($pendingContact->email, 'Verify your email address')
        ->once();

    $formsService->sendVerifySubscribeEmail($pendingContact, $mailingList);
});

test('A verify unsubscribe email is sent to a contact on unsubscribe', function() {
    $mailingList = createMailingList();
    $contact = createContact();

    $formsService = Mockery::mock(FormsService::class)->makePartial();
    $formsService->shouldReceive('sendEmail')
        ->withSomeOfArgs($contact->email, 'Verify unsubscribe')
        ->once();

    $formsService->sendVerifyUnsubscribeEmail($contact, $mailingList);
});

test('A verify unsubscribe email is sent to a contact on unsubscribe from all mailing lists', function() {
    $contact = createContact();

    $formsService = Mockery::mock(FormsService::class)->makePartial();
    $formsService->shouldReceive('sendEmail')
        ->withSomeOfArgs($contact->email, 'Verify unsubscribe')
        ->once();

    $formsService->sendVerifyUnsubscribeAllEmail($contact);
});

test('Subscribing and then unsubscribing a contact works', function() {
    $mailingList = createMailingList();
    $contact = createContact();
    Campaign::$plugin->forms->subscribeContact($contact, $mailingList);
    $subscribedMailingLists = $contact->getSubscribedMailingLists();

    expect($subscribedMailingLists)
        ->toHaveCount(1)
        ->and($subscribedMailingLists[0]->id)
        ->toBe($mailingList->id);

    Campaign::$plugin->forms->unsubscribeContact($contact, $mailingList);
    $subscribedMailingLists = $contact->getSubscribedMailingLists();

    expect($subscribedMailingLists)
        ->toBeEmpty();
});

test('A contact can be unsubscribed from all mailing lists', function() {
    $mailingList1 = createMailingList();
    $mailingList2 = createMailingList();
    $contact = createContact();
    Campaign::$plugin->forms->subscribeContact($contact, $mailingList1);
    Campaign::$plugin->forms->subscribeContact($contact, $mailingList2);

    Campaign::$plugin->forms->unsubscribeContactFromAllMailingLists($contact);

    expect($contact->getSubscribedMailingLists())
        ->toBeEmpty()
        ->and($contact->getMailingListSubscriptionStatus($mailingList1->id))
        ->toBe('unsubscribed')
        ->and($contact->getMailingListSubscriptionStatus($mailingList2->id))
        ->toBe('unsubscribed');
});

test('Subscribing results in a truncated source if too long', function() {
    $mailingList = createMailingList();
    $contact = createContact();
    $source = StringHelper::randomString(252);
    Campaign::$plugin->forms->subscribeContact($contact, $mailingList, 'web', $source . StringHelper::randomString(10));

    /** @var ContactMailingListRecord $contactMailingListRecord */
    $contactMailingListRecord = ContactMailingListRecord::find()
        ->where([
            'contactId' => $contact->id,
            'mailingListId' => $mailingList->id,
        ])
        ->one();

    expect($contactMailingListRecord->source)
        ->toBe($source . '...');
});

test('Updating a contact modifies its last activity timestamp', function() {
    $contact = createContact();
    Campaign::$plugin->forms->updateContact($contact);
    $contact = Campaign::$plugin->contacts->getContactById($contact->id);

    expect($contact->lastActivity)
        ->not->toBeNull();
});
