<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\test\fixtures\elements;

use craft\base\ElementInterface;
use craft\test\fixtures\elements\BaseElementFixture;
use putyourlightson\campaign\elements\ContactElement;

/**
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.10.0
 */

abstract class ContactElementFixture extends BaseElementFixture
{
    /**
     * @inheritdoc
     */
    protected function createElement(): ElementInterface
    {
        return new ContactElement();
    }
}
