<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\fieldlayoutelements\mailinglists;

use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\BaseNativeField;
use craft\helpers\UrlHelper;
use putyourlightson\campaign\assets\ContactEditAsset;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\MailingListElement;

/**
 * @since 2.0.0
 */
class MailingListContactFieldLayoutElement extends BaseNativeField
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
        Craft::$app->getView()->registerAssetBundle(ContactEditAsset::class);

        $limit = 50;
        $contactsQuery = ContactElement::find()
            ->mailingListId($element->getCanonicalId())
            ->limit($limit);
        $contacts = $contactsQuery->all();
        $total = $contactsQuery->count();

        $viewAllUrl = UrlHelper::cpUrl('campaign/contacts/view', [
            'site' => Craft::$app->getSites()->getCurrentSite()->handle,
            'source' => 'mailingList:' . $element->uid,
        ]);

        // TODO: remove in 3.0.0, when element index URLs include the source, added in Craft 4.3.0.
        if (version_compare(Craft::$app->getVersion(), '4.3.0', '<')) {
            $viewAllUrl = UrlHelper::cpUrl('campaign/contacts/view/' . $element->id);
        }

        return Craft::$app->getView()->renderTemplate(
            'campaign/mailinglists/_includes/contacts',
            [
                'mailingList' => $element->getCanonical(),
                'contacts' => $contacts,
                'total' => $total,
                'limit' => $limit,
                'viewAllUrl' => $viewAllUrl,
            ],
        );
    }
}
