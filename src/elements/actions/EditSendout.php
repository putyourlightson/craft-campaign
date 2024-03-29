<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */
namespace putyourlightson\campaign\elements\actions;

use Craft;
use craft\elements\actions\Edit;

/**
 * Allows only `modifiable` sendouts to be edited.
 * This class extends the `Edit` action so that it is not added twice.
 * @see Element::actions()
 *
 * @property-read string $triggerLabel
 * @property-read null $triggerHtml
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
