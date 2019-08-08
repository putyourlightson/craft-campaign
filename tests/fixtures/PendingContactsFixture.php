<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaigntests\fixtures;

use craft\test\Fixture;
use putyourlightson\campaign\records\CampaignTypeRecord;
use putyourlightson\campaign\records\MailingListTypeRecord;
use putyourlightson\campaign\records\PendingContactRecord;

/**
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.10.0
 */

class PendingContactsFixture extends Fixture
{
    // Public Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    public $dataFile = __DIR__ . '/data/pending-contacts.php';

    /**
     * @inheritdoc
     */
    public $modelClass = PendingContactRecord::class;
}
