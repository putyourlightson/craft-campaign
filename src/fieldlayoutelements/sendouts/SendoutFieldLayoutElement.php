<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\fieldlayoutelements\sendouts;

use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\BaseNativeField;
use craft\helpers\Template;
use putyourlightson\campaign\assets\SendoutEditAsset;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\CampaignElement;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\elements\SegmentElement;
use putyourlightson\campaign\elements\SendoutElement;
use putyourlightson\campaign\helpers\SettingsHelper;

/**
 * @since 2.0.0
 */
class SendoutFieldLayoutElement extends BaseNativeField
{
    /**
     * @inheritdoc
     */
    public string $attribute = 'sendout';

    /**
     * @inheritdoc
     */
    protected function inputHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        return null;
    }

    /**
     * @inheritdoc
     * @param SendoutElement $element
     */
    public function formHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        if (!$element->getIsModifiable()) {
            return '';
        }

        Craft::$app->getView()->registerAssetBundle(SendoutEditAsset::class);

        $titleFieldHtml = '';
        if (Campaign::$plugin->settings->showSendoutTitleField === true) {
            $titleField = new SendoutTitleField();
            $titleFieldHtml = Template::raw($titleField->formHtml($element));
        }

        $siteId = $element->siteId;
        $variables = [
            'editable' => !$static,
            'sendout' => $element,
            'schedule' => $element->getSchedule(),
            'titleFieldHtml' => $titleFieldHtml,
            'fromNameEmailOptions' => SettingsHelper::getFromNameEmailOptions($siteId),
            'campaignElementType' => CampaignElement::class,
            'campaignElementCriteria' => [
                'siteId' => $siteId,
            ],
            'mailingListElementType' => MailingListElement::class,
            'mailingListElementCriteria' => [
                'siteId' => $siteId,
            ],
            'contactElementType' => ContactElement::class,
        ];

        if (Campaign::$plugin->getIsPro()) {
            // Segment element selector variables
            $variables['segmentElementType'] = SegmentElement::class;
            $variables['segmentElementCriteria'] = [
                'siteId' => $siteId,
            ];
        }

        return Craft::$app->getView()->renderTemplate(
            'campaign/sendouts/_includes/fields',
            $variables,
        );
    }
}
