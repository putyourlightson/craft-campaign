<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\services;

use Craft;
use craft\base\Component;
use craft\events\ConfigEvent;
use craft\helpers\ProjectConfig;
use craft\helpers\StringHelper;
use craft\models\FieldLayout;
use putyourlightson\campaign\elements\ContactElement;

class ContactsService extends Component
{
    /**
     * @since 2.0.0
     */
    public const CONFIG_CONTACTFIELDLAYOUT_KEY = 'campaign.contactFieldLayout';

    /**
     * Returns a contact by ID.
     */
    public function getContactById(int $contactId): ?ContactElement
    {
        /** @var ContactElement|null */
        return ContactElement::find()
            ->id($contactId)
            ->status(null)
            ->one();
    }

    /**
     * Returns an array of contacts by IDs
     *
     * @param int[] $contactIds
     * @return ContactElement[]
     */
    public function getContactsByIds(?array $contactIds): array
    {
        if (empty($contactIds)) {
            return [];
        }

        /** @var ContactElement[] */
        return ContactElement::find()
            ->id($contactIds)
            ->status(null)
            ->fixedOrder()
            ->all();
    }

    /**
     * Returns contact by user ID.
     */
    public function getContactByUserId(int $userId): ?ContactElement
    {
        if (!$userId) {
            return null;
        }

        /** @var ContactElement|null */
        return ContactElement::find()
            ->userId($userId)
            ->status(null)
            ->one();
    }

    /**
     * Returns a contact by CID.
     */
    public function getContactByCid(string $cid): ?ContactElement
    {
        if (!$cid) {
            return null;
        }

        /** @var ContactElement|null */
        return ContactElement::find()
            ->cid($cid)
            ->status(null)
            ->one();
    }

    /**
     * Returns a contact by email.
     */
    public function getContactByEmail(string $email, bool $trashed = false): ?ContactElement
    {
        if (!$email) {
            return null;
        }

        /** @var ContactElement|null */
        return ContactElement::find()
            ->email($email)
            ->status(null)
            ->trashed($trashed)
            ->one();
    }

    /**
     * Saves the contact field layout.
     *
     * @since 2.0.0
     */
    public function saveContactFieldLayout(FieldLayout $fieldLayout): bool
    {
        $projectConfig = Craft::$app->getProjectConfig();
        $fieldLayoutConfig = $fieldLayout->getConfig();
        $uid = StringHelper::UUID();

        $projectConfig->set(self::CONFIG_CONTACTFIELDLAYOUT_KEY, [$uid => $fieldLayoutConfig], 'Save the contact field layout');

        return true;
    }

    /**
     * Handles a changed contact field layout.
     *
     * @since 2.0.0
     */
    public function handleChangedContactFieldLayout(ConfigEvent $event): void
    {
        $data = $event->newValue;

        // Make sure all fields are processed
        ProjectConfig::ensureAllFieldsProcessed();

        $fieldsService = Craft::$app->getFields();

        // Save the field layout
        $layout = FieldLayout::createFromConfig(reset($data));
        $layout->id = $fieldsService->getLayoutByType(ContactElement::class)->id;
        $layout->type = ContactElement::class;
        $layout->uid = key($data);
        $fieldsService->saveLayout($layout);
    }
}
