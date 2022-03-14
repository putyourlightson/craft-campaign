<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\fieldlayoutelements\mailinglists;

use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\BaseNativeField;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\MailingListElement;

/**
 * @since 2.0.0
 */
class MailingListContactField extends BaseNativeField
{
    /**
     * @inheritdoc
     */
    public string $attribute = 'mailinglist-contact';

    /**
     * @inheritdoc
     */
    protected function inputHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        return null;
    }

    /**
     * @inheritdoc
     * @param MailingListElement $element
     */
    public function formHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        $limit = 100;
        $contactsQuery = ContactElement::find()
            ->mailingListId($element->id)
            ->limit($limit);
        $contacts = $contactsQuery->all();
        $total = $contactsQuery->count();

        return Craft::$app->view->renderTemplate(
            'campaign/mailinglists/_includes/contacts',
            [
                'mailingList' => $element,
                'contacts' => $contacts,
                'total' => $total,
                'limit' => $limit,
            ],
        );
    }
}
