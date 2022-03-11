<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\fieldlayoutelements\contacts;

use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\TitleField;

/**
 * @since 2.0.0
 */
class EmailField extends TitleField
{
    /**
     * @inheritdoc
     */
    public string $attribute = 'email';

    /**
     * @inheritdoc
     */
    public function defaultLabel(?ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('campaign', 'Email');
    }
}
