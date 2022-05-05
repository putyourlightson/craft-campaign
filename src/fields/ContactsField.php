<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\fields;

use Craft;
use craft\elements\conditions\ElementConditionInterface;
use craft\fields\BaseRelationField;
use putyourlightson\campaign\elements\ContactElement;

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

    /**
     * @inheritdoc
     */
    protected function createSelectionCondition(): ?ElementConditionInterface
    {
        return ContactElement::createCondition();
    }
}
