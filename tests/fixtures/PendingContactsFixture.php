<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaigntests\fixtures;

use craft\test\ActiveFixture;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\records\PendingContactRecord;

/**
 * @since 1.10.0
 */
class PendingContactsFixture extends ActiveFixture
{
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
    public ?int $mailingListId = null;

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
