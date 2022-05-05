<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaigntests\fixtures;

use craft\test\ActiveFixture;
use putyourlightson\campaign\records\MailingListTypeRecord;

/**
 * @since 1.10.0
 */
class MailingListTypesFixture extends ActiveFixture
{
    /**
     * @inheritdoc
     */
    public $dataFile = __DIR__ . '/data/mailing-list-types.php';

    /**
     * @inheritdoc
     */
    public $modelClass = MailingListTypeRecord::class;
}
