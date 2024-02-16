<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\elements\db;

use craft\elements\db\ElementQuery;
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
     * @inheritdoc
     */
    protected function beforePrepare(): bool
    {
        $this->joinElementTable('campaign_segments');

        $this->query->select([
            'campaign_segments.contactCondition',
        ]);

        return parent::beforePrepare();
    }
}
