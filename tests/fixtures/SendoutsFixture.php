<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaigntests\fixtures;

use putyourlightson\campaign\test\fixtures\elements\SendoutElementFixture;

/**
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.10.0
 */

class SendoutsFixture extends SendoutElementFixture
{
    // Public Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    public $dataFile = __DIR__ . '/data/sendouts.php';

    /**
     * @inheritdoc
     */
    public $depends = [CampaignsFixture::class, MailingListsFixture::class];
}
