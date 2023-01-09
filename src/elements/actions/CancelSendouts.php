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
use putyourlightson\campaign\elements\SendoutElement;

/**
 * @property-read string $triggerLabel
 * @property-read string|null $confirmationMessage
 * @property-read string|null $triggerHtml
 */
class CancelSendouts extends ElementAction
{
    /**
     * @inheritdoc
     */
    public static function isDestructive(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getTriggerLabel(): string
    {
        return Craft::t('campaign', 'Cancel');
    }

    /**
     * @inheritdoc
     */
    public function getConfirmationMessage(): ?string
    {
        return Craft::t('campaign', 'Are you sure you want to cancel the selected sendouts?');
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
        validateSelection: function(\$selectedItems) {
            for (let i = 0; i < \$selectedItems.length; i++) {
                if (!Garnish.hasAttr(\$selectedItems.eq(i).find('.element'), 'data-cancellable')) {
                    return false;
                }
            }

            return true;
        }
    });
})();
EOD;

        Craft::$app->getView()->registerJs($js);

        return null;
    }

    /**
     * Performs the action on any elements that match the given criteria.
     */
    public function performAction(ElementQueryInterface $query): bool
    {
        /** @var SendoutElement[] $sendouts */
        $sendouts = $query->all();

        foreach ($sendouts as $sendout) {
            Campaign::$plugin->sendouts->cancelSendout($sendout);

            // Log it
            Campaign::$plugin->log('Sendout "{title}" cancelled by "{username}".', ['title' => $sendout->title]);
        }

        $this->setMessage(Craft::t('campaign', 'Sendouts cancelled.'));

        return true;
    }
}
