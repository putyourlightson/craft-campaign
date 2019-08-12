<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\test\fixtures\elements;

use craft\test\fixtures\elements\ElementFixture;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\MailingListElement;

/**
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.10.0
 */

abstract class MailingListElementFixture extends ElementFixture
{
    // Public Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    public $modelClass = MailingListElement::class;

    /**
     * @var array
     */
    public $mailingListTypeIds = [];

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function load()
    {
        foreach (Campaign::$plugin->mailingListTypes->getAllMailingListTypes() as $mailingListType) {
            $this->mailingListTypeIds[$mailingListType->handle] = $mailingListType->id;
        }

        parent::load();
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function isPrimaryKey(string $key): bool
    {
        return parent::isPrimaryKey($key) || in_array($key, ['mailingListTypeId', 'title']);
    }
}
