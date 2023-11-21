<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\validators;

use Craft;
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
        // Check if a contact exists with the email that is neither a draft nor a revision,
        // and that is not the canonical element.
        $contactId = ContactRecord::find()
            ->select(ContactRecord::tableName() . '.id')
            ->andWhere([
                'email' => $model->email,
                'element.draftId' => null,
                'element.revisionId' => null,
            ])
            ->andWhere([
                'not', [
                    ContactRecord::tableName() . '.id' => $model->canonicalId,
                ],
            ])
            ->scalar();

        if ($contactId) {
            $contact = ContactElement::findOne($contactId);
            if ($contact) {
                $message = Craft::t('campaign', 'A contact with the email "{email}" already exists. [View contact &raquo;]({url})', [
                    'email' => $model->email,
                    'url' => $contact->getCpEditUrl(),
                ]);
                $this->addError($model, $attribute, $message);
            }
        }
    }
}
