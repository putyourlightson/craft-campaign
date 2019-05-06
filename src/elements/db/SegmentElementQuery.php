<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\elements\db;

use craft\helpers\Db;
use putyourlightson\campaign\elements\SegmentElement;

use craft\elements\db\ElementQuery;
use yii\db\Connection;

/**
 * SegmentElementQuery
 *
 * @method SegmentElement[]|array all($db = null)
 * @method SegmentElement|array|null one($db = null)
 * @method SegmentElement|array|null nth(int $n, Connection $db = null)
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 */
class SegmentElementQuery extends ElementQuery
{
    // Properties
    // =========================================================================

    /**
     * @var string|null The segment type that the resulting segments must have.
     */
    public $segmentType;

    // Protected Methods
    // =========================================================================

    /**
     * Sets the [[segmentType]] property.
     *
     * @param string $value The property value
     *
     * @return static self reference
     */
    public function segmentType(string $value)
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
