<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\fieldlayoutelements\sendouts;

use craft\fieldlayoutelements\TitleField;

/**
 * @since 2.5.0
 */
class SendoutTitleField extends TitleField
{
    /**
     * @inerhitdoc
     */
    public string|array|null $class = 'title-field';

    /**
     * @inerhitdoc
     */
    public bool $autofocus = false;

    /**
     * @inerhitdoc
     */
    public bool $translatable = false;
}
