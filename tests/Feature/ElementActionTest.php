<?php

use craft\elements\actions\Delete;
use putyourlightson\campaign\elements\CampaignElement;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\elements\SegmentElement;
use putyourlightson\campaign\elements\SendoutElement;

test('An element type uses Craft’s native delete action', function(string $elementType) {
    $deleteActions = array_values(array_filter(
        $elementType::actions('*'),
        fn(mixed $action): bool => $action === Delete::class || $action instanceof Delete,
    ));

    expect($deleteActions)
        ->toHaveCount(1)
        ->and($deleteActions[0])
        ->toBe(Delete::class);
})->with([
    CampaignElement::class,
    ContactElement::class,
    MailingListElement::class,
    SegmentElement::class,
    SendoutElement::class,
]);
