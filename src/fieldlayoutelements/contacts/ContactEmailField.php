<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\fieldlayoutelements\contacts;

use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\TextField;

/**
 * @since 2.0.0
 */
class ContactEmailField extends TextField
{
    /**
     * @inheritdoc
     */
    public bool $mandatory = true;

    /**
     * @inheritdoc
     */
    public string $type = 'email';

    /**
     * @inheritdoc
     */
    public string $attribute = 'email';

    /**
     * @inheritdoc
     */
    public bool $translatable = false;

    /**
     * @inheritdoc
     */
    public ?int $maxlength = 255;

    /**
     * @inheritdoc
     */
    public bool $required = true;

    /**
     * @inheritdoc
     */
    public bool $autofocus = true;

    /**
     * @inheritdoc
     */
    public function defaultLabel(?ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('campaign', 'Email');
    }

    /**
     * @inheritdoc
     */
    protected function conditional(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    protected function statusClass(?ElementInterface $element = null, bool $static = false): ?string
    {
        if ($element && ($status = $element->getAttributeStatus('email'))) {
            return $status[0];
        }
        return null;
    }

    /**
     * @inheritdoc
     */
    protected function statusLabel(?ElementInterface $element = null, bool $static = false): ?string
    {
        if ($element && ($status = $element->getAttributeStatus('email'))) {
            return $status[1];
        }
        return null;
    }
}
