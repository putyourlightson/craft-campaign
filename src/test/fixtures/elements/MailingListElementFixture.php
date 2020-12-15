<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\test\fixtures\elements;

use craft\base\ElementInterface;
use craft\test\fixtures\elements\BaseElementFixture;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\MailingListElement;

/**
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.10.0
 */

abstract class MailingListElementFixture extends BaseElementFixture
{
    /**
     * @var array
     */
    public $mailingListTypeIds = [];

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

    /**
     * @inheritdoc
     */
    protected function createElement(): ElementInterface
    {
        return new MailingListElement();
    }
}
