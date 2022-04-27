<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\fieldlayoutelements\sendouts;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\BaseNativeField;
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
class SendoutField extends BaseNativeField
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

        $siteId = $element->siteId;
        $variables = [
            'editable' => !$static,
            'sendout' => $element,
            'schedule' => $element->getSchedule(),
            'fromNameEmailOptions' => SettingsHelper::getFromNameEmailOptions($siteId),
            'campaignElementType' => CampaignElement::class,
            'campaignElementCriteria' => [
                'siteId' => $siteId,
                'status' => [CampaignElement::STATUS_SENT, CampaignElement::STATUS_PENDING],
            ],
            'mailingListElementType' => MailingListElement::class,
            'mailingListElementCriteria' => [
                'siteId' => $siteId,
                'status' => Element::STATUS_ENABLED,
            ],
            'contactElementType' => ContactElement::class,
            'contactElementCriteria' => [
                'status' => Element::STATUS_ENABLED,
            ],
        ];

        if (Campaign::$plugin->getIsPro()) {
            // Segment element selector variables
            $variables['segmentElementType'] = SegmentElement::class;
            $variables['segmentElementCriteria'] = [
                'siteId' => $siteId,
                'status' => Element::STATUS_ENABLED,
            ];
        }

        return Craft::$app->getView()->renderTemplate(
            'campaign/sendouts/_includes/fields',
            $variables,
        );
    }
}
