<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\fieldlayoutelements\segments;

use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\TitleField;
use craft\models\FieldLayoutTab;

/**
 * @since 2.0.0
 */
class SegmentFieldLayoutTab extends FieldLayoutTab
{
    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        $this->name = Craft::t('campaign', 'Segment');
    }

    /**
     * @inheritdoc
     */
    public function getElements(): array
    {
        return [
            new TitleField(),
            new SegmentField(),
        ];
    }
}
