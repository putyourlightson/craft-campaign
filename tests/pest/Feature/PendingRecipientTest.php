<?php

use putyourlightson\campaign\Campaign;

/**
 * Tests calculating the pending recipient count of sendouts.
 */

test('A sendout’s pending recipient count equals the sum of its mailing list subscribers', function() {
    $sendout = createSendoutWithSubscribedContact();

    expect(Campaign::$plugin->sendouts->getPendingRecipientCount($sendout))
        ->toBe(1);
});

test('A sendout’s pending recipient count does not include complained subscribers', function() {
    $sendout = createSendoutWithSubscribedContact(attributes: ['complained' => new DateTime()]);

    expect(Campaign::$plugin->sendouts->getPendingRecipientCount($sendout))
        ->toBe(0);
});

test('A sendout’s pending recipient count does not include bounced subscribers', function() {
    $sendout = createSendoutWithSubscribedContact(attributes: ['bounced' => new DateTime()]);

    expect(Campaign::$plugin->sendouts->getPendingRecipientCount($sendout))
        ->toBe(0);
});

test('A sendout’s pending recipient count does not include blocked subscribers', function() {
    $sendout = createSendoutWithSubscribedContact(attributes: ['blocked' => new DateTime()]);

    expect(Campaign::$plugin->sendouts->getPendingRecipientCount($sendout))
        ->toBe(0);
});
