<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaigntests\fixtures;

use putyourlightson\campaign\test\fixtures\elements\MailingListElementFixture;

/**
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.10.0
 */

class MailingListsFixture extends MailingListElementFixture
{
    // Public Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    public $dataFile = __DIR__ . '/data/mailing-lists.php';

    /**
     * @inheritdoc
     */
    public $depends = [MailingListTypesFixture::class];
}
