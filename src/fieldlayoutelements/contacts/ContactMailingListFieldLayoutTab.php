<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\fieldlayoutelements\contacts;

use Craft;
use craft\base\ElementInterface;
use craft\models\FieldLayoutTab;

/**
 * @since 2.0.0
 */
class ContactMailingListFieldLayoutTab extends FieldLayoutTab
{
    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        $this->name = Craft::t('campaign', 'Mailing Lists');
    }

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
            new ContactMailingListFieldLayoutElement(),
        ];
    }
}
