<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\assets;

use Craft;
use craft\helpers\Json;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset as CraftCpAsset;
use craft\web\View;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\SegmentElement;
use putyourlightson\campaign\elements\SendoutElement;
use yii\web\View as BaseView;

class CpAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = '@putyourlightson/campaign/resources';

    /**
     * @inheritdoc
     */
    public $depends = [
        CraftCpAsset::class,
    ];

    /**
     * @inheritdoc
     */
    public $css = [
        'css/cp.css',
    ];

    /**
     * @inheritdoc
     */
    public function registerAssetFiles($view): void
    {
        parent::registerAssetFiles($view);

        if ($view instanceof View) {
            $this->_registerTranslations($view);
        }

        $this->_registerPublishableTypes($view);
    }

    /**
     * @param View $view
     */
    private function _registerTranslations(View $view): void
    {
        $view->registerTranslations('app', [
            '(blank)',
            '<span class="visually-hidden">Characters left:</span> {chars, number}',
            'A server error occurred.',
            'Actions',
        ]);
    }

    private function _registerPublishableTypes(View $view): void
    {
        $campaignTypes = [];
        foreach (Campaign::$plugin->campaignTypes->getAllCampaignTypes() as $campaignType) {
            $campaignTypes[] = [
                'id' => $campaignType->id,
                'handle' => $campaignType->handle,
                'name' => Craft::t('campaign', $campaignType->name),
                'siteId' => $campaignType->siteId,
                'uid' => $campaignType->uid,
            ];
        }
        $publishableCampaignTypes = Json::encode($campaignTypes, JSON_UNESCAPED_UNICODE);

        $mailingListTypes = [];
        foreach (Campaign::$plugin->mailingListTypes->getAllMailingListTypes() as $mailingListType) {
            $mailingListTypes[] = [
                'id' => $mailingListType->id,
                'handle' => $mailingListType->handle,
                'name' => Craft::t('campaign', $mailingListType->name),
                'siteId' => $mailingListType->siteId,
                'uid' => $mailingListType->uid,
            ];
        }
        $publishableMailingListTypes = Json::encode($mailingListTypes, JSON_UNESCAPED_UNICODE);

        $segmentTypes = [];
        $i = 1;
        foreach (SegmentElement::segmentTypes() as $segmentType => $segmentTypeLabel) {
            $segmentTypes[] = [
                'id' => $i,
                'handle' => $segmentType,
                'name' => Craft::t('campaign', $segmentTypeLabel),
            ];
            $i++;
        }
        $publishableSegmentTypes = Json::encode($segmentTypes, JSON_UNESCAPED_UNICODE);

        $sendoutTypes = [];
        $i = 1;
        foreach (SendoutElement::sendoutTypes() as $sendoutType => $sendoutTypeLabel) {
            $sendoutTypes[] = [
                'id' => $i,
                'handle' => $sendoutType,
                'name' => Craft::t('campaign', $sendoutTypeLabel),
            ];
            $i++;
        }
        $publishableSendoutTypes = Json::encode($sendoutTypes, JSON_UNESCAPED_UNICODE);

        $js = <<<JS
window.Craft.publishableCampaignTypes = $publishableCampaignTypes;
window.Craft.publishableMailingListTypes = $publishableMailingListTypes;
window.Craft.publishableSegmentTypes = $publishableSegmentTypes;
window.Craft.publishableSendoutTypes = $publishableSendoutTypes;
JS;

        $view->registerJs($js, BaseView::POS_HEAD);
    }
}
