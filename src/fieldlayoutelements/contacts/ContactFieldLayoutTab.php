<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\fieldlayoutelements\contacts;

use craft\base\ElementInterface;
use craft\models\FieldLayoutTab;

/**
 * @since 2.0.0
 */
class ContactFieldLayoutTab extends FieldLayoutTab
{
    /**
     * @inheritdoc
     */
    public function showInForm(?ElementInterface $element = null): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getElements(): array
    {
        return [
            new ContactEmailField(),
        ];
    }
}
