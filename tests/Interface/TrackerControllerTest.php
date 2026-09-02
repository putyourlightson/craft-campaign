<?php

use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\controllers\TrackerController;

/**
 * Tests the public tracking endpoints.
 */

beforeEach(function() {
    Campaign::$plugin->settings->requireUnsubscribeConfirmation = false;
    Craft::$app->request->headers->set('Accept', 'application/json');
    Craft::$app->request->setBodyParams([]);
    Craft::$app->request->setQueryParams([]);
    unset($_POST[Craft::$app->request->methodParam]);
});

afterEach(function() {
    Campaign::$plugin->settings->requireUnsubscribeConfirmation = false;
    Craft::$app->request->setBodyParams([]);
    Craft::$app->request->setQueryParams([]);
    unset($_POST[Craft::$app->request->methodParam]);
});

test('An unsubscribe link requires confirmation when enabled', function() {
    Campaign::$plugin->settings->requireUnsubscribeConfirmation = true;
    $contact = createContact();
    $sendout = createSendoutWithSubscribedContact($contact);
    Craft::$app->request->setQueryParams([
        'cid' => $contact->cid,
        'sid' => $sendout->sid,
    ]);

    $response = (new TrackerController('t', Campaign::$plugin))->actionUnsubscribe();

    expect($response->data)
        ->toMatchArray([
            'success' => false,
            'confirmationRequired' => true,
        ])
        ->and($contact->getMailingListSubscriptionStatus($sendout->mailingListIds[0]))
        ->toBe('subscribed');
});

test('A confirmed unsubscribe request updates the subscription', function() {
    Campaign::$plugin->settings->requireUnsubscribeConfirmation = true;
    $contact = createContact();
    $sendout = createSendoutWithSubscribedContact($contact);
    $_POST[Craft::$app->request->methodParam] = 'post';
    Craft::$app->request->setBodyParams([
        'cid' => $contact->cid,
        'sid' => $sendout->sid,
        'confirm' => '1',
        Craft::$app->request->csrfParam => Craft::$app->request->getCsrfToken(),
    ]);

    (new TrackerController('t', Campaign::$plugin))->actionUnsubscribe();

    expect($contact->getMailingListSubscriptionStatus($sendout->mailingListIds[0]))
        ->toBe('unsubscribed');
});

test('An unsubscribe all request updates every subscription', function() {
    $contact = createContact();
    $sendout = createSendoutWithSubscribedContact($contact);
    $mailingList = createMailingList();
    Campaign::$plugin->forms->subscribeContact($contact, $mailingList);
    Craft::$app->request->setQueryParams([
        'cid' => $contact->cid,
        'sid' => $sendout->sid,
    ]);

    (new TrackerController('t', Campaign::$plugin))->actionUnsubscribeAll();

    expect($contact->getSubscribedMailingLists())
        ->toBeEmpty()
        ->and($contact->getMailingListSubscriptionStatus($sendout->mailingListIds[0]))
        ->toBe('unsubscribed')
        ->and($contact->getMailingListSubscriptionStatus($mailingList->id))
        ->toBe('unsubscribed');
});
