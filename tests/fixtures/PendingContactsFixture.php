<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaigntests\fixtures;

use craft\test\Fixture;
use putyourlightson\campaign\elements\MailingListElement;
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

    /**
     * @inheritdoc
     */
    public $depends = [MailingListsFixture::class];

    /**
     * @var int|null
     */
    public $mailingListId;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function load()
    {
        $mailingList = MailingListElement::find()->mailingListType('mailingListType2')->one();
        $this->mailingListId = $mailingList ? $mailingList->id : null;

        parent::load();
    }
}
