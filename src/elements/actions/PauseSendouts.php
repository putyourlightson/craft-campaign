<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */
namespace putyourlightson\campaign\elements\actions;

use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\SendoutElement;
use putyourlightson\campaign\helpers\LogHelper;

use Craft;
use craft\base\ElementAction;
use craft\elements\db\ElementQueryInterface;
use craft\errors\ElementNotFoundException;
use craft\helpers\Json;
use yii\base\Exception;

/**
 * PauseSendouts
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 *
 * @property void   $triggerHtml
 * @property string $triggerLabel
 * @property mixed  $confirmationMessage
 */
class PauseSendouts extends ElementAction
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getTriggerLabel(): string
    {
        return Craft::t('campaign', 'Pause');
    }

    /**
     * @inheritdoc
     */
    public function getConfirmationMessage()
    {
        return Craft::t('campaign', 'Are you sure you want to pause the selected sendouts?');
    }

    /**
     * @inheritdoc
     */
    public function getTriggerHtml()
    {
        $type = Json::encode(static::class);

        $js = <<<EOD
(function()
{
    var trigger = new Craft.ElementActionTrigger({
        type: {$type},
        batch: true,
        validateSelection: function(\$selectedItems)
        {
            for (var i = 0; i < \$selectedItems.length; i++)
            {
                if (!Garnish.hasAttr(\$selectedItems.eq(i).find('.element'), 'data-pausable'))
                {
                    return false;
                }
            }

            return true;
        }
    });
})();
EOD;

        Craft::$app->getView()->registerJs($js);
    }

    /**
     * Performs the action on any elements that match the given criteria.
     *
     * @param ElementQueryInterface $query The element query defining which elements the action should affect.
     *
     * @return bool Whether the action was performed successfully.
     * @throws \Throwable
     * @throws ElementNotFoundException
     * @throws Exception
     */
    public function performAction(ElementQueryInterface $query): bool
    {
        /** @var SendoutElement[] $sendouts */
        $sendouts = $query->all();

        foreach ($sendouts as $sendout) {
            Campaign::$plugin->sendouts->pauseSendout($sendout);

            // Log it
            LogHelper::logUserAction('Sendout "{title}" paused by "{username}".', ['title' => $sendout->title], __METHOD__);
        }

        $this->setMessage(Craft::t('campaign', 'Sendouts paused.'));

        return true;
    }
}
