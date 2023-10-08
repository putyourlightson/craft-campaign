<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\validators;

use Craft;
use craft\base\Element;
use craft\validators\UniqueValidator;
use putyourlightson\campaign\elements\SendoutElement;

/**
 * @since 2.9.0
 */
class SendoutMailingListsValidator extends UniqueValidator
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
        $mailingLists = $model->getMailingLists();

        if (empty($mailingLists)) {
            $this->addError($model, $attribute, Craft::t('campaign', 'At least one mailing list must be selected.'));

            return;
        }

        foreach ($mailingLists as $mailingList) {
            $status = $mailingList->getStatus();
            if ($status !== Element::STATUS_ENABLED) {
                $this->addError($model, $attribute, Craft::t('campaign', 'One or more disabled mailing lists were selected.'));

                return;
            }
        }
    }
}
