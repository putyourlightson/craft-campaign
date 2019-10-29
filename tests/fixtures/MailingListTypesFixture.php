<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaigntests\fixtures;

use craft\test\Fixture;
use putyourlightson\campaign\records\MailingListTypeRecord;

/**
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.10.0
 */

class MailingListTypesFixture extends Fixture
{
    // Public Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    public $dataFile = __DIR__ . '/data/mailing-list-types.php';

    /**
     * @inheritdoc
     */
    public $modelClass = MailingListTypeRecord::class;
}
