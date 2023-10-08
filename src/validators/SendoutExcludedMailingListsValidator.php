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
class SendoutExcludedMailingListsValidator extends UniqueValidator
{
    /**
     * @inheritdoc
     *
     * @param SendoutElement $model
     */
    public function validateAttribute($model, $attribute): void
    {
        $mailingLists = $model->getExcludedMailingLists();

        foreach ($mailingLists as $mailingList) {
            $status = $mailingList->getStatus();
            if ($status !== Element::STATUS_ENABLED) {
                $this->addError($model, $attribute, Craft::t('campaign', 'One or more disabled excluded mailing lists were selected.'));

                return;
            }
        }
    }
}
