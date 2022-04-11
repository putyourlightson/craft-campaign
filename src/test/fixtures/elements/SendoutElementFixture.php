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
    public ?int $senderId;

    /**
     * @var int|null
     */
    public ?int $campaignId;

    /**
     * @var array|null
     */
    public ?array $mailingListIds = null;

    /**
     * @var array|null
     */
    public ?array $segmentIds = null;

    /**
     * @inheritdoc
     */
    public function load(): void
    {
        $this->senderId = 1;

        $campaign = CampaignElement::find()->title('Campaign 1')->one();
        $this->campaignId = $campaign ? $campaign->id : null;

        $this->mailingListIds = MailingListElement::find()->ids();
        $this->segmentIds = SegmentElement::find()->ids();

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
