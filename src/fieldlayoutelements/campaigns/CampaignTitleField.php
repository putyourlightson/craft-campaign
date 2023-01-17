<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\fieldlayoutelements\campaigns;

use craft\base\ElementInterface;
use craft\fieldlayoutelements\entries\EntryTitleField;
use craft\helpers\Html;
use putyourlightson\campaign\elements\CampaignElement;
use putyourlightson\campaign\fieldlayoutelements\NonTranslatableTitleField;
use yii\base\InvalidArgumentException;

/**
 * @since 2.5.0
 * @see EntryTitleField
 */
class CampaignTitleField extends NonTranslatableTitleField
{
    /**
     * @inheritdoc
     */
    protected function selectorInnerHtml(): string
    {
        return
            Html::tag('span', '', [
                'class' => ['fld-title-field-icon', 'fld-field-hidden', 'hidden'],
            ]) .
            parent::selectorInnerHtml();
    }

    /**
     * @inheritdoc
     */
    public function inputHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        if (!$element instanceof CampaignElement) {
            throw new InvalidArgumentException('CampaignTitleField can only be used in campaign field layouts.');
        }

        if (!$element->getCampaignType()->hasTitleField && !$element->hasErrors('title')) {
            return null;
        }

        return parent::inputHtml($element, $static);
    }
}
