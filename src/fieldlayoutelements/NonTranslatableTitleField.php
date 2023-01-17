<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\fieldlayoutelements;

use craft\fieldlayoutelements\TitleField;

/**
 * @since 2.5.1
 */
class NonTranslatableTitleField extends TitleField
{
    /**
     * @inheritdoc
     */
    public bool $translatable = false;
}
