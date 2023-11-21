<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\elements\actions;

use Craft;
use craft\base\ElementAction;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\Json;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\ContactElement;

/**
 * @since 2.11.0
 *
 * @property-read string $triggerLabel
 * @property-read string|null $triggerHtml
 */
class SubscribeContacts extends ElementAction
{
    public ?int $mailingListId = null;
    public ?string $mailingListTitle = null;

    /**
     * @inheritdoc
     */
    public function getTriggerLabel(): string
    {
        return Craft::t('campaign', 'Subscribe to “{mailingList}”', ['mailingList' => $this->mailingListTitle]);
    }

    /**
     * @inheritdoc
     */
    public function getTriggerHtml(): ?string
    {
        $type = Json::encode(static::class);

        $js = <<<EOD
(function()
{
    var trigger = new Craft.ElementActionTrigger({
        type: $type,
        batch: true,
        validateSelection: true,
    });
})();
EOD;

        Craft::$app->getView()->registerJs($js);

        return null;
    }

    /**
     * @inheritdoc
     */
    public function performAction(ElementQueryInterface $query): bool
    {
        $mailingList = Campaign::$plugin->mailingLists->getMailingListById($this->mailingListId);

        /** @var ContactElement[] $contacts */
        $contacts = $query->all();

        foreach ($contacts as $contact) {
            Campaign::$plugin->mailingLists->addContactInteraction($contact, $mailingList, 'subscribed');

            Campaign::$plugin->log('Contact “{email}” subscribed to “{mailingList}”.', [
                'email' => $contact->email,
                'mailingList' => $this->mailingListTitle,
            ]);
        }

        $this->setMessage(Craft::t('campaign', 'Contacts successfully subscribed to “{mailingList}”.', ['mailingList' => $this->mailingListTitle]));

        return true;
    }
}
