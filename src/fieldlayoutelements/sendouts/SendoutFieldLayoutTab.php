<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\fieldlayoutelements\sendouts;

use Craft;
use craft\fieldlayoutelements\TitleField;
use craft\models\FieldLayoutTab;

/**
 * @since 2.0.0
 */
class SendoutFieldLayoutTab extends FieldLayoutTab
{
    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        $this->name = Craft::t('campaign', 'Sendout');
    }

    /**
     * @inheritdoc
     */
    public function getElements(): array
    {
        return [
            new TitleField(),
            new SendoutField(),
        ];
    }
}
