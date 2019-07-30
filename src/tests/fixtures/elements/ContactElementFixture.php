<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\tests\fixtures\elements;

use craft\test\fixtures\elements\ElementFixture;
use putyourlightson\campaign\elements\ContactElement;

/**
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.10.0
 */

abstract class ContactElementFixture extends ElementFixture
{
    // Public Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    public $modelClass = ContactElement::class;

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function isPrimaryKey(string $key): bool
    {
        return parent::isPrimaryKey($key) || in_array($key, ['email']);
    }
}
