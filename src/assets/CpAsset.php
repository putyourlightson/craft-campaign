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
    public $js = [
        'js/Campaign.js',
        'js/CampaignIndex.js',
        'js/ContactIndex.js',
        'js/MailingListIndex.js',
        'js/SegmentIndex.js',
        'js/SendoutIndex.js',
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

        $this->_registerEditableTypes($view);
    }

    private function _registerTranslations(View $view): void
    {
        $view->registerTranslations('app', [
            '(blank)',
            '<span class="visually-hidden">Characters left:</span> {chars, number}',
            'A server error occurred.',
            'Actions',
        ]);
    }

    private function _registerEditableTypes(BaseView $view): void
    {
        $editableCampaignTypes = Json::encode($this->_getEditableCampaignTypes());
        $editableMailingListTypes = Json::encode($this->_getEditableMailingListTypes());
        $editableSegmentTypes = Json::encode($this->_getEditableSegmentTypes());
        $editableSendoutTypes = Json::encode($this->_getEditableSendoutTypes());

        $js = <<<JS
window.Craft.editableCampaignTypes = $editableCampaignTypes;
window.Craft.editableMailingListTypes = $editableMailingListTypes;
window.Craft.editableSegmentTypes = $editableSegmentTypes;
window.Craft.editableSendoutTypes = $editableSendoutTypes;
JS;

        $view->registerJs($js, BaseView::POS_HEAD);
    }

    private function _getEditableCampaignTypes(): array
    {
        $campaignTypes = [];

        foreach (Campaign::$plugin->campaignTypes->getEditableCampaignTypes() as $campaignType) {
            $campaignTypes[] = [
                'id' => $campaignType->id,
                'handle' => $campaignType->handle,
                'name' => $campaignType->name,
                'siteId' => $campaignType->siteId,
                'uid' => $campaignType->uid,
            ];
        }

        return $campaignTypes;
    }

    private function _getEditableMailingListTypes(): array
    {
        $mailingListTypes = [];

        foreach (Campaign::$plugin->mailingListTypes->getEditableMailingListTypes() as $mailingListType) {
            $mailingListTypes[] = [
                'id' => $mailingListType->id,
                'handle' => $mailingListType->handle,
                'name' => $mailingListType->name,
                'siteId' => $mailingListType->siteId,
                'uid' => $mailingListType->uid,
            ];
        }

        return $mailingListTypes;
    }

    private function _getEditableSegmentTypes(): array
    {
        $segmentTypes = [];

        foreach (SegmentElement::segmentTypes() as $handle => $name) {
            $segmentTypes[] = [
                'handle' => $handle,
                'name' => Craft::t('campaign', $name),
            ];
        }

        return $segmentTypes;
    }

    private function _getEditableSendoutTypes(): array
    {
        $sendoutTypes = [];

        foreach (SendoutElement::sendoutTypes() as $handle => $name) {
            $sendoutTypes[] = [
                'handle' => $handle,
                'name' => Craft::t('campaign', $name),
            ];
        }

        return $sendoutTypes;
    }
}
