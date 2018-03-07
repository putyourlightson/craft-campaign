<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\fields;

use putyourlightson\campaign\elements\ContactElement ;

use Craft;
use craft\fields\BaseRelationField;

/**
 * ContactsField
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0   
 */
class ContactsField extends BaseRelationField
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('campaign', 'Contacts');
    }

    /**
     * @inheritdoc
     */
    protected static function elementType(): string
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
