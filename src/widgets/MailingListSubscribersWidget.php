<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\widgets;

use Craft;
use craft\base\Widget;
use putyourlightson\campaign\assets\WidgetAsset;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\MailingListElement;

/**
 * @property-read string|null $title
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
    public ?int $dateRange = null;

    /**
     * @var array|null
     */
    public ?array $mailingListIds = null;

    /**
     * @var array|null
     */
    public ?array $mailingListId = null;

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

        $selectedMailingLists = Campaign::$plugin->mailingLists->getMailingListsByIds($this->mailingListIds);

        $unit = $this->dateRange ? Craft::t('campaign', 'new subscribers') : Craft::t('campaign', 'subscribers');

        $subscribers = 0;
        $mailingLists = $selectedMailingLists;
        if (empty($mailingLists)) {
            $mailingLists = Campaign::$plugin->mailingLists->getAllMailingLists();
        }
        foreach ($mailingLists as $mailingList) {
            $subscribers += $mailingList->getSubscribedCount();
        }

        return Craft::$app->getView()->renderTemplate('campaign/_widgets/subscribers/widget', [
            'selectedMailingLists' => $selectedMailingLists,
            'subscribers' => $subscribers,
            'unit' => $unit,
            'dateRange' => $this->dateRange,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('campaign/_widgets/subscribers/settings', [
            'widget' => $this,
            'mailingListElementType' => MailingListElement::class,
            'mailingLists' => Campaign::$plugin->mailingLists->getMailingListsByIds($this->mailingListIds),
        ]);
    }
}
