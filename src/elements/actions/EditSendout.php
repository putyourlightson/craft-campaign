<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */
namespace putyourlightson\campaign\elements\actions;

use Craft;
use craft\elements\actions\Edit;

/**
 * @property-read null $triggerHtml
 * @property-read string $triggerLabel
 * @property-read null|string $confirmationMessage
 */
class EditSendout extends Edit
{
    /**
     * @inheritdoc
     */
    public function getTriggerLabel(): string
    {
        return Craft::t('campaign', 'Edit sendout');
    }

    /**
     * @inheritdoc
     */
    public function getTriggerHtml(): ?string
    {
        Craft::$app->getView()->registerJsWithVars(fn($type) => <<<JS
(() => {
    new Craft.ElementActionTrigger({
        type: $type,
        batch: false,
        validateSelection: \$selectedItems => Garnish.hasAttr(\$selectedItems.find('.element'), 'data-savable') && Garnish.hasAttr(\$selectedItems.find('.element'), 'data-modifiable'),
        activate: \$selectedItems => {
            const \$element = \$selectedItems.find('.element:first');
            Craft.createElementEditor(\$element.data('type'), \$element);
        },
    });
})();
JS, [static::class]);

        return null;
    }
}
