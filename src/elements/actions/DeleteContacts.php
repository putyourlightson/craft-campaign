<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */
namespace putyourlightson\campaign\elements\actions;

use Craft;
use craft\elements\actions\Delete;
use craft\elements\db\ElementQueryInterface;
use putyourlightson\campaign\Campaign;

/**
 * DeleteContacts
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.14.0
 *
 * @property void   $triggerHtml
 * @property string $triggerLabel
 * @property mixed  $confirmationMessage
 */
class DeleteContacts extends Delete
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getTriggerLabel(): string
    {
        return Craft::t('campaign', 'Hard Delete');
    }

    /**
     * @inheritdoc
     */
    public function getConfirmationMessage()
    {
        return Craft::t('campaign', 'Are you sure you want to hard delete the selected contacts? This action cannot be undone.');
    }

    /**
     * @inheritdoc
     */
    public function performAction(ElementQueryInterface $query): bool
    {
        $elementsService = Craft::$app->getElements();

        foreach ($query->all() as $contact) {
            $elementsService->deleteElement($contact, true);
        }

        $this->setMessage(Craft::t('campaign', 'Contacts hard deleted.'));

        return true;
    }
}
