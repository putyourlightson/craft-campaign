<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\fieldlayoutelements\sendouts;

use putyourlightson\campaign\fieldlayoutelements\NonTranslatableTitleField;

/**
 * @since 2.5.0
 */
class SendoutTitleField extends NonTranslatableTitleField
{
    /**
     * @inerhitdoc
     */
    public string|array|null $class = 'title-field';

    /**
     * @inerhitdoc
     */
    public bool $autofocus = false;
}
