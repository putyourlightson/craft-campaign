<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\elements\conditions\contacts;

use Craft;
use craft\base\conditions\BaseElementSelectConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;
use putyourlightson\campaign\elements\CampaignElement;
use putyourlightson\campaign\elements\db\ContactElementQuery;
use putyourlightson\campaign\records\ContactCampaignRecord;

/**
 * @since 2.0.0
 */
class CampaignActivityConditionRule extends BaseElementSelectConditionRule implements ElementConditionRuleInterface
{
    /**
     * @inheritdoc
     */
    public string $operator = 'opened';

    /**
     * @inheritdoc
     */
    protected bool $reloadOnOperatorChange = true;

    /**
     * @inheritdoc
     */
    protected function elementType(): string
    {
        return CampaignElement::class;
    }

    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return Craft::t('campaign', 'Campaign Activity');
    }

    /**
     * @inheritdoc
     */
    public function getExclusiveQueryParams(): array
    {
        return [];
    }

    public function modifyQuery(ElementQueryInterface $query): void
    {
        /** @var ContactElementQuery $query */
        $query->innerJoin(ContactCampaignRecord::tableName(), '[[campaign_contacts.id]] = [[contactId]]');

        $query->andWhere(['not', [
            $this->_operatorColumn($this->operator) => null,
        ]]);

        $elementId = $this->getElementId();
        if ($elementId !== null) {
            $query->andWhere(['campaignId' => $elementId]);
        }
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(): string
    {
        return match ($this->operator) {
            'openedCampaign', 'clickedCampaign' => parent::inputHtml(),
            default => '',
        };
    }

    protected function operators(): array
    {
        return [
            'opened',
            'clicked',
            'openedCampaign',
            'clickedCampaign',
        ];
    }

    /**
     * @inheritdoc
     */
    protected function operatorLabel(string $operator): string
    {
        return match ($operator) {
            'opened' => Craft::t('campaign', 'opened any campaign'),
            'clicked' => Craft::t('campaign', 'clicked a link in any campaign'),
            'openedCampaign' => Craft::t('campaign', 'opened the campaign'),
            'clickedCampaign' => Craft::t('campaign', 'clicked a link in the campaign'),
            default => parent::operatorLabel($operator),
        };
    }

    /**
     * @inheritdoc
     */
    public function matchElement(ElementInterface $element): bool
    {
        $query = ContactCampaignRecord::find()
            ->where([
                'contactId' => $element->id,
            ])
            ->andWhere(['not', [
                $this->_operatorColumn($this->operator) => null,
            ],
        ]);

        $elementId = $this->getElementId();
        if ($elementId !== null) {
            $query->andWhere(['campaignId' => $elementId]);
        }

        return $query->exists();
    }

    /**
     * Returns the column to query on based on the operator.
     */
    private function _operatorColumn(string $operator): string
    {
        return match ($operator) {
            'clicked', 'clickedCampaign' => 'clicked',
            default => 'opened',
        };
    }
}
