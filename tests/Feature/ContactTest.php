<?php

/**
 * Tests properties of contacts.
 */

test('A contact with the same email address as another contact cannot be saved', function() {
    $contact1 = createContact();
    $contact2 = createContact($contact1->email);

    expect($contact2->hasErrors('email'))
        ->toBeTrue();
});

test('A contact with the same email address as a soft-deleted contact can be saved', function() {
    $contact1 = createContact();
    Craft::$app->elements->deleteElement($contact1);
    $contact2 = createContact($contact1->email);

    expect($contact2->hasErrors())
        ->toBeFalse();
});
