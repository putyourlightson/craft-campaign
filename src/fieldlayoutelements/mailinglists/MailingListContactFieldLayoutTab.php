<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\fieldlayoutelements\mailinglists;

use Craft;
use craft\base\ElementInterface;
use craft\models\FieldLayoutTab;

/**
 * @since 2.0.0
 */
class MailingListContactFieldLayoutTab extends FieldLayoutTab
{
    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        $this->name = Craft::t('campaign', 'Contacts');
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
            new MailingListContactFieldLayoutElement(),
        ];
    }
}
