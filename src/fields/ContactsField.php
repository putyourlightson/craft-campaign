<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\fields;

use Craft;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\ElementCollection;
use craft\fields\BaseRelationField;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\db\ContactElementQuery;

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
    public static function phpType(): string
    {
        return sprintf('\\%s|\\%s<\\%s>', ContactElementQuery::class, ElementCollection::class, ContactElement::class);
    }

    /**
     * @inheritdoc
     */
    protected function createSelectionCondition(): ?ElementConditionInterface
    {
        return ContactElement::createCondition();
    }
}
