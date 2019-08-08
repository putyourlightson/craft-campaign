<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\tests\fixtures\elements;

use craft\test\fixtures\elements\ElementFixture;
use putyourlightson\campaign\elements\CampaignElement;
use putyourlightson\campaign\elements\MailingListElement;
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
     * @var array
     */
    public $campaignIds = [];

    /**
     * @var array
     */
    public $mailingListIds = [];

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        /** @var CampaignElement $campaign */
        foreach (CampaignElement::findAll() as $campaign) {
            $this->campaignIds[$campaign->slug] = $campaign->id;
        }

        /** @var MailingListElement $mailingList */
        foreach (MailingListElement::findAll() as $mailingList) {
            $this->mailingListIds[$mailingList->slug] = $mailingList->id;
        }
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function isPrimaryKey(string $key): bool
    {
        return parent::isPrimaryKey($key) || in_array($key, ['campaignId', 'title']);
    }
}
