<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaigntests\fixtures;

use putyourlightson\campaign\test\fixtures\elements\ContactElementFixture;

/**
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.10.0
 */

class ContactsFixture extends ContactElementFixture
{
    // Public Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    public $dataFile = __DIR__ . '/data/contacts.php';
}
