<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\fieldlayoutelements\sendouts;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\BaseNativeField;
use putyourlightson\campaign\assets\SendoutEditAsset;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\CampaignElement;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\elements\SegmentElement;
use putyourlightson\campaign\elements\SendoutElement;

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
        Craft::$app->view->registerAssetBundle(SendoutEditAsset::class);

        $siteId = $element->siteId;
        $variables = [
            'sendout' => $element,
            'fromNameEmailOptions' => Campaign::$plugin->settings->getFromNameEmailOptions($siteId),
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
            'segmentElementType' => SegmentElement::class,
        ];

        if (Campaign::$plugin->getIsPro()) {
            // Segment element selector variables
            $variables['segmentElementType'] = SegmentElement::class;
            $variables['segmentElementCriteria'] = [
                'siteId' => $siteId,
                'status' => Element::STATUS_ENABLED,
            ];
        }

        return Craft::$app->view->renderTemplate(
            'campaign/sendouts/_includes/fields',
            $variables,
        );
    }
}
