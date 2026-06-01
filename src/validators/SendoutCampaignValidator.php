<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\validators;

use Craft;
use craft\validators\UniqueValidator;
use putyourlightson\campaign\elements\SendoutElement;

/**
 * @since 2.9.0
 */
class SendoutCampaignValidator extends UniqueValidator
{
    /**
     * @inheritdoc
     */
    public $skipOnEmpty = false;

    /**
     * @inheritdoc
     *
     * @param SendoutElement $model
     */
    public function validateAttribute($model, $attribute): void
    {
        $campaign = $model->getCampaign();

        if ($campaign === null) {
            $this->addError($model, $attribute, Craft::t('campaign', 'A campaign must be selected.'));

            return;
        }

        if (!$campaign->hasSendableStatus()) {
            $this->addError($model, $attribute, Craft::t('campaign', 'An unsendable campaign has been selected.'));
        }
    }
}
