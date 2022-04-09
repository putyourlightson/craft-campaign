<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaigntests\fixtures;

use craft\test\ActiveFixture;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\records\PendingContactRecord;

/**
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.10.0
 */

class PendingContactsFixture extends ActiveFixture
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
    public ?int $mailingListId;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function load(): void
    {
        $mailingList = MailingListElement::find()->mailingListType('mailingListType2')->one();
        $this->mailingListId = $mailingList->id;

        parent::load();
    }
}
