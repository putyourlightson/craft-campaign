<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\fields;

use putyourlightson\campaign\elements\ContactElement;

use Craft;
use craft\fields\BaseRelationField;

class ContactsField extends BaseRelationField
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('campaign', 'Campaign Contacts');
    }

    /**
     * @inheritdoc
     */
    public static function elementType(): string
    {
        return ContactElement::class;
    }

    /**
     * @inheritdoc
     */
    public static function defaultSelectionLabel(): string
    {
        return Craft::t('campaign', 'Add a contact');
    }
}
