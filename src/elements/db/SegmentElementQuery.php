<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\elements\db;

use craft\elements\db\ElementQuery;
use craft\helpers\Db;

use putyourlightson\campaign\elements\SegmentElement;
use yii\db\Connection;

/**
 * @method SegmentElement[]|array all($db = null)
 * @method SegmentElement|array|null one($db = null)
 * @method SegmentElement|array|null nth(int $n, Connection $db = null)
 */
class SegmentElementQuery extends ElementQuery
{
    /**
     * @var string|null The segment type that the resulting segments must have.
     */
    public ?string $segmentType;

    /**
     * Sets the [[segmentType]] property.
     */
    public function segmentType(string $value): static
    {
        $this->segmentType = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function beforePrepare(): bool
    {
        $this->joinElementTable('campaign_segments');

        $this->query->select([
            'campaign_segments.segmentType',
            'campaign_segments.conditions',
        ]);

        if ($this->segmentType) {
            $this->subQuery->andWhere(Db::parseParam('campaign_segments.segmentType', $this->segmentType));
        }

        return parent::beforePrepare();
    }
}
