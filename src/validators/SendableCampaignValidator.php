<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\validators;

use Craft;
use craft\validators\UniqueValidator;
use putyourlightson\campaign\elements\CampaignElement;
use putyourlightson\campaign\elements\SendoutElement;

/**
 * @since 2.9.0
 */
class SendableCampaignValidator extends UniqueValidator
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

        $status = $campaign->getStatus();

        if ($status !== CampaignElement::STATUS_PENDING && $status !== CampaignElement::STATUS_SENT) {
            $this->addError($model, $attribute, Craft::t('campaign', 'You have selected an unsendable campaign.'));
        }
    }
}
