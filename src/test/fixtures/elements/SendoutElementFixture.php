<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\test\fixtures\elements;

use craft\base\ElementInterface;
use craft\test\fixtures\elements\BaseElementFixture;
use putyourlightson\campaign\elements\CampaignElement;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\elements\SegmentElement;
use putyourlightson\campaign\elements\SendoutElement;

/**
 * @since 1.10.0
 */
abstract class SendoutElementFixture extends BaseElementFixture
{
    /**
     * @var int|null
     */
    public ?int $campaignId;

    /**
     * @var string|null
     */
    public ?string $mailingListIds;

    /**
     * @var string|null
     */
    public ?string $segmentIds;

    /**
     * @inheritdoc
     */
    public function load(): void
    {
        $campaign = CampaignElement::find()->one();
        $this->campaignId = $campaign ? $campaign->id : null;

        $mailingListIds = MailingListElement::find()->ids();
        $this->mailingListIds = implode(',', $mailingListIds);

        $segmentIds = SegmentElement::find()->ids();
        $this->segmentIds = implode(',', $segmentIds);

        parent::load();
    }

    /**
     * @inheritdoc
     */
    protected function createElement(): ElementInterface
    {
        return new SendoutElement();
    }
}
