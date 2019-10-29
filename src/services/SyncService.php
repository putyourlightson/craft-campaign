<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\services;

use craft\events\ElementEvent;
use craft\events\UserEvent;
use craft\events\UserGroupsAssignEvent;
use craft\helpers\ArrayHelper;
use craft\services\Elements;
use craft\services\Users;
use DateTime;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\jobs\SyncJob;
use putyourlightson\campaign\records\ContactMailingListRecord;

use Craft;
use craft\base\Component;
use craft\elements\User;
use yii\base\Event;

/**
 * SyncService
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.2.0
 */
class SyncService extends Component
{
    // Constants
    // =========================================================================

    /**
     * @event SyncEvent
     */
    const EVENT_BEFORE_SYNC = 'beforeSync';

    /**
     * @event SyncEvent
     */
    const EVENT_AFTER_SYNC = 'afterSync';

    // Public Methods
    // =========================================================================

    public function registerUserEvents()
    {
        $events = [
            [Elements::class, Elements::EVENT_AFTER_SAVE_ELEMENT],
            [Elements::class, Elements::EVENT_AFTER_DELETE_ELEMENT],
            [Users::class, Users::EVENT_AFTER_ACTIVATE_USER],
            [Users::class, Users::EVENT_AFTER_ASSIGN_USER_TO_GROUPS],
            [Users::class, Users::EVENT_AFTER_SUSPEND_USER],
            [Users::class, Users::EVENT_AFTER_UNSUSPEND_USER],
            [Users::class, Users::EVENT_AFTER_VERIFY_EMAIL],
        ];

        foreach ($events as $event) {
            Event::on($event[0], $event[1], [$this, 'handleUserEvent']);
        }
    }

    /**
     * Handles a user event
     *
     * @param Event $event
     */
    public function handleUserEvent(Event $event)
    {
        // Ensure pro version
        if (!Campaign::$plugin->getIsPro()) {
            return;
        }

        if ($event instanceof UserEvent) {
            $this->syncUser($event->user);
        }
        else if ($event instanceof ElementEvent && $event->element instanceof User) {
            // If user was deleted
            if ($event->name == Elements::EVENT_AFTER_DELETE_ELEMENT) {
                $this->deleteUser($event->element);
            }
            else {
                $this->syncUser($event->element);
            }
        }
        else if ($event instanceof UserGroupsAssignEvent) {
            $user = Craft::$app->getUsers()->getUserById($event->userId);

            if ($user !== null) {
                $this->syncUser($user);
            }
        }
    }

    /**
     * Queues a sync
     *
     * @param MailingListElement $mailingList
     */
    public function queueSync(MailingListElement $mailingList)
    {
        // Add sync job to queue
        Craft::$app->getQueue()->push(new SyncJob(['mailingListId' => $mailingList->id]));
    }

    /**
     * Syncs a user
     *
     * @param User $user
     */
    public function syncUser(User $user)
    {
        // Get user's user group IDs
        $userGroups = $user->getGroups();
        $userGroupIds = ArrayHelper::getColumn($userGroups, 'id');

        $mailingLists = MailingListElement::find()
            ->synced(true)
            ->all();

        foreach ($mailingLists as $mailingList) {
            // If the mailing list is synced with user's user group ID
            if (in_array($mailingList->syncedUserGroupId, $userGroupIds, true)) {
                $this->syncUserMailingList($user, $mailingList);
            }
            else {
                $this->removeUserMailingList($user, $mailingList);
            }
        }
    }

    /**
     * Deletes a user
     *
     * @param User $user
     */
    public function deleteUser(User $user)
    {
        $contact = Campaign::$plugin->contacts->getContactByUserId($user->id);

        if ($contact !== null) {
            Craft::$app->getElements()->deleteElement($contact);
        }
    }

    /**
     * Syncs a user to a contact in a mailing list
     *
     * @param User $user
     * @param MailingListElement $mailingList
     */
    public function syncUserMailingList(User $user, MailingListElement $mailingList)
    {
        $contact = Campaign::$plugin->contacts->getContactByUserId($user->id);

        if ($contact === null) {
            $contact = Campaign::$plugin->contacts->getContactByEmail($user->email);
        }

        if ($contact === null) {
            $contact = new ContactElement();
        }

        $contact->userId = $user->id;
        $contact->email = $user->email;

        // Set first and last name from user fields if they exist
        if (isset($contact->firstName)) {
            $contact->firstName = $user->firstName;
        }
        if (isset($contact->lastName)) {
            $contact->lastName = $user->lastName;
        }

        // Set contact's field values from user's field values
        $contact->setFieldValues($user->getFieldValues());

        if (!Craft::$app->getElements()->saveElement($contact)) {
            return;
        }

        // Get contact mailing list record
        $contactMailingListRecord = ContactMailingListRecord::find()
            ->where([
                'contactId' => $contact->id,
                'mailingListId' => $mailingList->id,
            ])
            ->one();

        // If user is active and contact mailing list record does not exist then create it and subscribe
        if ($user->status == User::STATUS_ACTIVE && $contactMailingListRecord === null) {
            $contactMailingListRecord = new ContactMailingListRecord();
            $contactMailingListRecord->contactId = $contact->id;
            $contactMailingListRecord->mailingListId = $mailingList->id;

            $contactMailingListRecord->subscriptionStatus = 'subscribed';
            $contactMailingListRecord->subscribed = new DateTime();
            $contactMailingListRecord->sourceType = 'user';
            $contactMailingListRecord->source = $user->id;

            $contactMailingListRecord->save();
        }

        // If user is not active and contact mailing list record exists then delete it
        else if ($user->status != User::STATUS_ACTIVE && $contactMailingListRecord !== null) {
            $contactMailingListRecord->delete();
        }
    }

    /**
     * Removes a user synced contact from a mailing list
     *
     * @param User $user
     * @param MailingListElement $mailingList
     */
    public function removeUserMailingList(User $user, MailingListElement $mailingList)
    {
        $contact = Campaign::$plugin->contacts->getContactByUserId($user->id);

        if ($contact === null) {
            return;
        }

        // Get contact mailing list record
        $contactMailingListRecord = ContactMailingListRecord::find()
            ->where([
                'contactId' => $contact->id,
                'mailingListId' => $mailingList->id,
            ])
            ->one();

        if ($contactMailingListRecord !== null) {
            $contactMailingListRecord->delete();
        }
    }
}
