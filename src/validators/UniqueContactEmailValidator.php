<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\validators;

use craft\validators\UniqueValidator;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\records\ContactRecord;

/**
 * @since 2.0.2
 */
class UniqueContactEmailValidator extends UniqueValidator
{
    /**
     * @inheritdoc
     *
     * @param ContactElement $model
     */
    public function validateAttribute($model, $attribute): void
    {
        // Check if email exists for an element that is neither a draft nor a revision,
        // and that is not the canonical element.
        $exists = ContactRecord::find()
            ->andWhere([
                'email' => $model->email,
                'element.draftId' => null,
                'element.revisionId' => null,
            ])
            ->andWhere(['not', [
                ContactRecord::tableName() . '.id' => $model->canonicalId,
            ]])
            ->exists();

        if ($exists) {
            $this->addError($model, $attribute, $this->message);
        }
    }
}
