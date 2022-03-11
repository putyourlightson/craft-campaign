<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\fields;

use Craft;
use craft\fields\BaseRelationField;
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
}
