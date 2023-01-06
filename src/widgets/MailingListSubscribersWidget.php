<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\widgets;

use Craft;
use craft\base\Widget;
use putyourlightson\campaign\assets\WidgetAsset;

/**
 * @property-read string|null $bodyHtml
 * @property-read string|null $settingsHtml
 */
class MailingListSubscribersWidget extends Widget
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('campaign', 'Mailing List Subscribers');
    }

    /**
     * @inheritdoc
     */
    public static function icon(): ?string
    {
        return Craft::getAlias('@putyourlightson/campaign/icon.svg');
    }

    /**
     * @var int|null
     */
    public ?int $previousDays = null;

    /**
     * @inheritdoc
     */
    public function getTitle(): ?string
    {
        return '';
    }

    /**
     * @inheritdoc
     */
    public function getBodyHtml(): ?string
    {
        Craft::$app->getView()->registerAssetBundle(WidgetAsset::class);

        $value = 5;
        $unit = $this->previousDays ? Craft::t('campaign', 'new subscribers') : Craft::t('campaign', 'subscribers');

        return Craft::$app->getView()->renderTemplate('campaign/_widgets/subscribers/widget', [
            'value' => $value,
            'unit' => $unit,
            'previousDays' => $this->previousDays,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('campaign/_widgets/subscribers/settings', [
            'widget' => $this,
        ]);
    }
}
