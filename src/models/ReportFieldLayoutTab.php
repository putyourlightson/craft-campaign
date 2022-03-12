<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\models;

use craft\base\ElementInterface;
use craft\models\FieldLayoutTab;
use putyourlightson\campaign\fieldlayoutelements\reports\ReportField;

/**
 * @since 2.0.0
 *
 * @property-read ReportField[] $fields
 */
class ReportFieldLayoutTab extends FieldLayoutTab
{
    /**
     * @inheritdoc
     */
    public ?string $name = 'Report';

    /**
     * @inheritdoc
     */
    public function getElements(): array
    {
        return [new ReportField()];
    }

    /**
     * @inheritdoc
     */
    public function showInForm(?ElementInterface $element = null): bool
    {
        return true;
    }
}
