<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\validators;

use Craft;
use craft\validators\UniqueValidator;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\SendoutElement;

/**
 * @since 2.9.0
 */
class SendableContactsValidator extends UniqueValidator
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
        $contacts = $model->getContacts();

        if (empty($contacts)) {
            $this->addError($model, $attribute, Craft::t('campaign', 'At least one contact must be selected.'));

            return;
        }

        foreach ($contacts as $contact) {
            $status = $contact->getStatus();
            if ($status !== ContactElement::STATUS_ACTIVE) {
                $this->addError($model, $attribute, Craft::t('campaign', 'One or more disabled contacts were selected.'));

                return;
            }
        }
    }
}
