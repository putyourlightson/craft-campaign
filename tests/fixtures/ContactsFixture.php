<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaigntests\fixtures;

use putyourlightson\campaign\test\fixtures\elements\ContactElementFixture;

/**
 * @since 1.10.0
 */
class ContactsFixture extends ContactElementFixture
{
    /**
     * @inheritdoc
     */
    public $dataFile = __DIR__ . '/data/contacts.php';
}
