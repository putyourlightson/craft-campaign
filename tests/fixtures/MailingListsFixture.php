<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaigntests\fixtures;

use putyourlightson\campaign\test\fixtures\elements\MailingListElementFixture;

/**
 * @since 1.10.0
 */
class MailingListsFixture extends MailingListElementFixture
{
    /**
     * @inheritdoc
     */
    public $dataFile = __DIR__ . '/data/mailing-lists.php';

    /**
     * @inheritdoc
     */
    public $depends = [MailingListTypesFixture::class];

    /**
     * @var int[]
     */
    public array $mailingListTypeIds = [];
}
