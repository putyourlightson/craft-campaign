<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */
namespace putyourlightson\campaign\elements\actions;

use Craft;
use craft\base\Element;
use craft\elements\actions\View;

/**
 * Allows only `pending` or `sent` campaigns to be viewed.
 * This class extends the `View` action so that it is not added twice.
 * @see Element::actions()
 *
 * @property-read string $triggerLabel
 * @property-read null $triggerHtml
 */
class ViewCampaign extends View
{
    /**
     * @inheritdoc
     */
    public function getTriggerLabel(): string
    {
        return Craft::t('campaign', 'View campaign');
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
        bulk: false,
        validateSelection: \$selectedItems => {
            const \$element = \$selectedItems.find('.element');
            return (
                \$element.data('url') &&
                (\$element.data('status') === 'pending' || \$element.data('status') === 'sent')
            );
        },
        activate: \$selectedItems => {
            window.open(\$selectedItems.find('.element').data('url'));
        },
    });
})();
JS, [static::class]);

        return null;
    }
}
