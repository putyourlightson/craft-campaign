<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\fields;

use Craft;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\ElementCollection;
use craft\fields\BaseRelationField;
use putyourlightson\campaign\elements\db\MailingListElementQuery;
use putyourlightson\campaign\elements\MailingListElement;

/**
 * @since 1.13.0
 */
class MailingListsField extends BaseRelationField
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('campaign', 'Campaign Mailing Lists');
    }

    /**
     * @inheritdoc
     */
    public static function elementType(): string
    {
        return MailingListElement::class;
    }

    /**
     * @inheritdoc
     */
    public static function defaultSelectionLabel(): string
    {
        return Craft::t('campaign', 'Add a mailing list');
    }

    /**
     * @inheritdoc
     */
    public static function valueType(): string
    {
        return sprintf('\\%s|\\%s<\\%s>', MailingListElementQuery::class, ElementCollection::class, MailingListElement::class);
    }

    /**
     * @inheritdoc
     */
    protected function createSelectionCondition(): ?ElementConditionInterface
    {
        return MailingListElement::createCondition();
    }
}
