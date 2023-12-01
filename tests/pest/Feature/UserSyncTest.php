<?php

/**
 * Tests syncing user groups with mailing lists.
 */

use craft\helpers\App;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\ContactElement;

beforeEach(function() {
    Campaign::$plugin->sync->registerUserEvents();
});

test('Syncing a user group with a mailing list creates contacts and subscribes them', function() {
    $userGroup = Craft::$app->userGroups->getGroupByHandle(App::env('TEST_USER_GROUP_HANDLE'));
    createMailingList(['syncedUserGroupId' => $userGroup->id]);
    $user = createUser();
    $userGroup = Craft::$app->userGroups->getGroupByHandle(App::env('TEST_USER_GROUP_HANDLE'));
    Craft::$app->users->assignUserToGroups($user->id, [$userGroup->id]);
    $contact = ContactElement::find()->email($user->email)->one();

    expect($contact)
        ->not->toBeNull()
        ->and($contact->getSubscribedMailingLists())
        ->toHaveCount(1);
});
