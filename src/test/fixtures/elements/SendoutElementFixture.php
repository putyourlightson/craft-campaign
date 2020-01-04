<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\test\fixtures\elements;

use craft\test\fixtures\elements\ElementFixture;
use putyourlightson\campaign\elements\CampaignElement;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\elements\SegmentElement;
use putyourlightson\campaign\elements\SendoutElement;

/**
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.10.0
 */

abstract class SendoutElementFixture extends ElementFixture
{
    // Public Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    public $modelClass = SendoutElement::class;

    /**
     * @var int|null
     */
    public $campaignId;

    /**
     * @var array
     */
    public $mailingListIds = [];

    /**
     * @var array
     */
    public $segmentIds = [];

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function load()
    {
        $campaign = CampaignElement::find()->one();
        $this->campaignId = $campaign ? $campaign->id : null;

        $mailingList = MailingListElement::find()->one();
        $this->mailingListIds = $mailingList ? $mailingList->id : null;

        $segment = SegmentElement::find()->one();
        $this->segmentIds = $segment ? $segment->id : null;

        parent::load();
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function isPrimaryKey(string $key): bool
    {
        return parent::isPrimaryKey($key) || in_array($key, ['title']);
    }
}
