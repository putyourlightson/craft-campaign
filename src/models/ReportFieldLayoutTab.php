<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\models;

use Craft;
use craft\base\ElementInterface;
use craft\models\FieldLayoutTab;
use putyourlightson\campaign\elements\CampaignElement;
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
    public function init(): void
    {
        parent::init();

        $this->name = Craft::t('campaign', 'Report');
    }

    /**
     * @inheritdoc
     */
    public function getElements(): array
    {
        return [
            new ReportField(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function showInForm(?ElementInterface $element = null): bool
    {
        return $element->getStatus() == CampaignElement::STATUS_SENT;
    }
}
